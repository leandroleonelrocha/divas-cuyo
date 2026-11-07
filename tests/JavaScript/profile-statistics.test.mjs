import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';
const source = readFileSync(new URL('../../public/js/profile-statistics.js', import.meta.url), 'utf8');
test('counts visible visit and WhatsApp without intercepting navigation', async () => {
    const handlers = {}, calls = [];
    const document = {
        visibilityState: 'visible',
        querySelector: () => ({ dataset: { sessionUrl: '/session', eventUrl: '/events' } }),
        addEventListener: (name, callback) => handlers[name] = callback,
    };
    vm.runInNewContext(source, { document, fetch: async (url, options) => {
        calls.push({ url, options });
        return { ok: true, json: async () => ({ token: 'csrf' }) };
    } });
    await new Promise(resolve => setImmediate(resolve));
    handlers.click({ target: { closest: () => true }, preventDefault() { throw new Error('Navigation blocked'); } });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(calls.filter(call => call.url === '/session').length, 1);
    assert.deepEqual(calls.filter(call => call.url === '/events').map(call => JSON.parse(call.options.body).event), ['visit', 'whatsapp']);
    assert.equal(calls.at(-1).options.keepalive, true);
});
test('tracking failure does not block clicks and hidden tabs do not count a visit', async () => {
    const handlers = {};
    let requests = 0;
    const document = {
        visibilityState: 'hidden',
        querySelector: () => ({ dataset: { sessionUrl: '/session', eventUrl: '/events' } }),
        addEventListener: (name, callback) => handlers[name] = callback,
    };
    vm.runInNewContext(source, { document, fetch: async () => { requests++; throw new Error('Offline'); } });
    assert.equal(requests, 0);
    handlers.click({ target: { closest: () => true }, preventDefault() { throw new Error('Navigation blocked'); } });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests, 1);
});
