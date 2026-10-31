<?php

namespace Tests\Feature\Account;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SpanishValidationTest extends TestCase
{
    public function test_validation_uses_spanish_messages_and_field_names(): void
    {
        app()->setLocale('es');
        $validator = Validator::make(
            ['email' => 'incorrecto', 'password' => 'abc', 'stage_name' => ''],
            ['email' => 'email', 'password' => 'min:8', 'stage_name' => 'required']
        );
        $this->assertSame('Ingresá un correo electrónico válido.', $validator->errors()->first('email'));
        $this->assertSame('El campo contraseña debe tener al menos 8 caracteres.', $validator->errors()->first('password'));
        $this->assertSame('El campo nombre artístico es obligatorio.', $validator->errors()->first('stage_name'));
    }
}
