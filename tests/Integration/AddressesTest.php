<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Address\DeleteMessage;
use Polkurier\Webservice\Dto\Request\Address\GetMessage;
use Polkurier\Webservice\Entity\Address\SaveAddressMessage;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;

#[Group('integration')]
#[CoversNothing]
final class AddressesTest extends IntegrationTestCase
{
    public function testTheAddressBookReads(): void
    {
        $this->assertReads(fn() => $this->sdk->addresses()->getAddresses());
    }

    public function testASenderIsSaved(): string
    {
        self::requireSandbox();

        $address = $this->sdk->addresses()->saveAddress(new SaveAddressMessage(
            name: 'SDK sender ' . \bin2hex(\random_bytes(3)),
            person: 'Jan Kowalski',
            street: 'Długa',
            houseNumber: '1',
            postcode: '61-001',
            city: 'Poznań',
            email: 'jan@example.com',
            phone: '500600700',
            type: AddressTypeEnum::Sender,
            company: 'Acme sp. z o.o.',
        ));

        self::assertNotSame('', $address->getId());

        return $address->getId();
    }

    #[Depends('testASenderIsSaved')]
    public function testTheSenderIsListedAndDeleted(string $id): void
    {
        $senders = $this->sdk->addresses()->getAddresses(new GetMessage(type: AddressTypeEnum::Sender));
        self::assertContains($id, \array_map(static fn($address) => $address->getId(), $senders));

        self::assertTrue(
            $this->sdk->addresses()->deleteAddress(new DeleteMessage(id: $id, type: AddressTypeEnum::Sender)),
        );
    }
}
