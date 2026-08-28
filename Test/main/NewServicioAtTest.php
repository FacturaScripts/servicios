<?php
/**
 * This file is part of Servicios plugin for FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Servicios\Controller\NewServicioAT;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class NewServicioAtTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // la página NewServicioAT se registra normalmente al desplegar el plugin;
        // la creamos aquí si el entorno de test no la tiene ya instalada
        $page = new Page();
        if (false === $page->load('NewServicioAT')) {
            $page->name = 'NewServicioAT';
            $page->title = 'NewServicioAT';
            $page->menu = 'sales';
            $page->showonmenu = false;
            $page->save();
        }
    }

    public function testSaveNewCustomerCreatesCustomer(): void
    {
        $user = $this->getRandomUser();
        $user->admin = true;
        $this->assertTrue($user->save());

        $codcliente = null;
        try {
            $data = $this->saveNewCustomer($user, [
                'name' => 'Cliente nuevo test',
                'cifnif' => '',
                'address' => 'Calle Test 1',
            ]);

            $this->assertTrue($data['saveNewCustomer'] ?? false);
            $this->assertArrayNotHasKey('duplicatedCifnif', $data);

            $codcliente = $data['codcliente'];
            $customer = new Cliente();
            $this->assertTrue($customer->load($codcliente));
            $this->assertEquals('Cliente nuevo test', $customer->nombre);
        } finally {
            if ($codcliente !== null) {
                $customer = new Cliente();
                if ($customer->load($codcliente)) {
                    $this->assertTrue($customer->delete());
                }
            }
            $this->assertTrue($user->delete());
        }
    }

    public function testSaveNewCustomerRejectsEmptyName(): void
    {
        $user = $this->getRandomUser();
        $user->admin = true;
        $this->assertTrue($user->save());

        try {
            $data = $this->saveNewCustomer($user, [
                'name' => '   ',
                'cifnif' => '',
            ]);

            $this->assertFalse($data['saveNewCustomer'] ?? true);
            $this->assertArrayNotHasKey('codcliente', $data);
        } finally {
            $this->assertTrue($user->delete());
        }
    }

    public function testSaveNewCustomerDetectsDuplicatedCifnif(): void
    {
        $user = $this->getRandomUser();
        $user->admin = true;
        $this->assertTrue($user->save());

        $existing = $this->getRandomCustomer();
        $existing->cifnif = 'B' . mt_rand(1, 999999);
        $this->assertTrue($existing->save());

        try {
            // sin confirmar, con un cifnif ya usado, no debe crear el cliente
            $data = $this->saveNewCustomer($user, [
                'name' => 'Cliente duplicado test',
                'cifnif' => $existing->cifnif,
                'cifnif_confirmed' => '0',
            ]);

            $this->assertFalse($data['saveNewCustomer'] ?? true);
            $this->assertTrue($data['duplicatedCifnif'] ?? false);
            $this->assertStringContainsString($existing->cifnif, $data['duplicatedCifnifMessage']);

            // confirmando, sí debe crear el cliente aunque el cifnif esté duplicado
            $data = $this->saveNewCustomer($user, [
                'name' => 'Cliente duplicado test',
                'cifnif' => $existing->cifnif,
                'cifnif_confirmed' => '1',
            ]);

            $this->assertTrue($data['saveNewCustomer'] ?? false);
            $this->assertArrayNotHasKey('duplicatedCifnif', $data);

            $newCustomer = new Cliente();
            $this->assertTrue($newCustomer->load($data['codcliente']));
            $this->assertEquals($existing->cifnif, $newCustomer->cifnif);

            $this->assertTrue($newCustomer->delete());
        } finally {
            $this->assertTrue($existing->delete());
            $this->assertTrue($user->delete());
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function saveNewCustomer(User $user, array $request): array
    {
        $controller = new NewServicioAT('NewServicioAT', '/NewServicioAT');
        $controller->request = new Request([
            'request' => array_merge([
                'action' => 'saveNewCustomer',
                'ajax' => true,
            ], $request),
        ]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $controller->privateCore($response, $user, $permissions);

        return json_decode($response->getContent(), true) ?? [];
    }
}
