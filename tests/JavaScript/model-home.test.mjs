import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../../public/js/model-home.js', import.meta.url), 'utf8');

function page(query = '') {
  const element = (dataset = {}) => ({
    dataset, hidden: false, textContent: '', attributes: {}, events: {},
    classList: { toggle() {} },
    setAttribute(key, value) { this.attributes[key] = value; },
    addEventListener(event, handler) { this.events[event] = handler; },
    querySelector() { return null; },
  });
  const cards = ['escort', 'virtual', 'escort masajistas', 'escort trans'].map((categories, i) =>
    element({ categories, service: categories.split(' ')[0], name: 'Perfil ' + i, key: String(i) }));
  const avatars = cards.map(card => element(card.dataset));
  const counter = element();
  const group = element();
  group.querySelectorAll = () => cards;
  group.querySelector = () => counter;
  const tabs = ['all', 'escort', 'virtual', 'masajistas', 'trans'].map(view => element({ view }));
  const ids = Object.fromEntries(['result-count', 'profiles-title', 'empty-state', 'empty-title',
    'empty-message', 'reset-filters', 'virtual-disclaimer'].map(id => [id, element()]));
  ids['profile-groups'] = { querySelectorAll: selector =>
    selector === '.province-group' ? [group] : selector === '.profile-card' ? cards : [] };
  runInNewContext(script, {
    document: {
      getElementById: id => ids[id] || null,
      querySelector: () => null,
      querySelectorAll: selector => selector === '[data-view]' ? tabs : avatars,
    },
    location: { hash: '', search: query },
    localStorage: { getItem: () => null },
    URLSearchParams,
  });
  return { cards, avatars, tabs, ids, counter, group };
}

test('buttons filter cards and avatars without the removed location/search controls', () => {
  const { cards, avatars, tabs, ids, counter } = page();
  for (const [view, expected] of [['all', 4], ['escort', 3], ['virtual', 1], ['masajistas', 1], ['trans', 1]]) {
    const tab = tabs.find(tab => tab.dataset.view === view);
    tab.events.click();
    assert.equal(cards.filter(card => !card.hidden).length, expected);
    assert.equal(avatars.filter(card => !card.hidden).length, expected);
    assert.equal(counter.textContent, expected + (expected === 1 ? ' perfil' : ' perfiles'));
    assert.equal(tab.attributes['aria-pressed'], 'true');
    assert.equal(tabs.filter(tab => tab.attributes['aria-pressed'] === 'true').length, 1);
    assert.equal(ids['virtual-disclaimer'].hidden, view !== 'virtual');
  }
  ids['reset-filters'].events.click();
  assert.equal(cards.filter(card => !card.hidden).length, 4);
});

test('category links initialize the filter and empty groups are hidden', () => {
  const { cards, tabs, ids, group } = page('?tipo=masajistas');
  assert.equal(cards.filter(card => !card.hidden).length, 1);
  assert.equal(cards[2].hidden, false);
  cards[2].dataset.categories = 'escort';
  tabs.find(tab => tab.dataset.view === 'masajistas').events.click();
  assert.equal(group.hidden, true);
  assert.equal(ids['empty-state'].hidden, false);
  ids['reset-filters'].events.click();
  assert.equal(group.hidden, false);
  assert.equal(ids['empty-state'].hidden, true);
});
