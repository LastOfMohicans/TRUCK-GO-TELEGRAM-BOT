<?php

namespace Tests\Telegram\Inline;

use App\Telegram\Inline\InlineMenuBase;
use Illuminate\Foundation\Testing\TestCase;

class InlineMenuBaseTest extends TestCase
{
    function testValidateCustomPercentDiscount()
    {
        $service = new InlineMenuBase();

        $valueName = 'скидка';
        $this->assertNull($service->validateCustomPercentDiscount(0.1, $valueName));
        $this->assertNull($service->validateCustomPercentDiscount(1, $valueName));
        $this->assertNull($service->validateCustomPercentDiscount(55.8, $valueName));

        $this->assertEquals("Значение поля {$valueName} не должно превышать 100.", $service->validateCustomPercentDiscount(101, $valueName));
        $this->assertEquals("Значение поля {$valueName} должно быть не менее 0.1.", $service->validateCustomPercentDiscount(0, $valueName));
        $this->assertEquals("Значение поля {$valueName} должно быть не менее 0.1.", $service->validateCustomPercentDiscount(-1.1, $valueName));

        $this->assertEquals("Поле {$valueName} должно быть числом.", $service->validateCustomPercentDiscount("sdf", $valueName));
    }
}
