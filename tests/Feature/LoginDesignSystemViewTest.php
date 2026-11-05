<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginDesignSystemViewTest extends TestCase
{
    public function test_tela_login_renderiza_com_tokens_do_design_system(): void
    {
        $response = $this->get(route('migration.login'));

        $response->assertOk();
        $response->assertSee('#F7F4ED');
        $response->assertSee('#176DA9');
        $response->assertSee('Um produto Anvy Softwares');
        $response->assertSee('Check Planilha');
    }
}
