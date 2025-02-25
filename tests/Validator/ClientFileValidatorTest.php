<?php

namespace App\Tests\Validator;

use App\Validator\ClientFileValidator;
use PHPUnit\Framework\TestCase;

class ClientFileValidatorTest extends TestCase
{
    private ClientFileValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ClientFileValidator();
    }

    public function testValidRow(): void
    {
        $row = ['1', 'Pauline Alexander', 'ceev@eva.ch', 'Cracow'];
        $this->assertTrue($this->validator->validateRow($row));
    }

    public function testInvalidColumnCount(): void
    {
        $row = ['1', 'Pauline Alexander', 'ceev@eva.ch'];
        $this->assertFalse($this->validator->validateRow($row));
    }

    public function testInvalidId(): void
    {
        $row = ['abc', 'Pauline Alexander', 'ceev@eva.ch', 'Cracow'];
        $this->assertFalse($this->validator->validateRow($row));
    }

    public function testInvalidName(): void
    {
        $row = ['1', '', 'ceev@eva.ch', 'Cracow'];
        $this->assertFalse($this->validator->validateRow($row));
    }

    public function testInvalidEmail(): void
    {
        $row = ['1', 'Pauline Alexander', 'invalid-email', 'Cracow'];
        $this->assertFalse($this->validator->validateRow($row));
    }

    public function testInvalidCity(): void
    {
        $row = ['1', 'Pauline Alexander', 'ceev@eva.ch', ''];
        $this->assertFalse($this->validator->validateRow($row));
    }

    public function testRowWithOnlySpaces(): void
    {
        $row = ['  ', '   ', '    ', '   '];
        $this->assertFalse($this->validator->validateRow($row));
    }
}
