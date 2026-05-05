<?php

namespace Oro\Bundle\DPDBundle\Migrations\Schema\v1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Converts DPD encrypted credential column from VARCHAR(255) to TEXT to fit AES-encrypted value.
 */
class ChangeEncryptedColumnsToText implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');

        if ($table->getColumn('dpd_cloud_user_token')->getType()->getName() === Types::TEXT) {
            return;
        }

        $table->changeColumn('dpd_cloud_user_token', ['type' => Type::getType(Types::TEXT), 'length' => null]);
    }
}
