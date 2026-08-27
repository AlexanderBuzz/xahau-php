<?php declare(strict_types=1);
/**
 * Xahau-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\Xahau_PHP\Test;

use Exception;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Types\SerializedType;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction;
use Hardcastle\Xahau_PHP\Xahau;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Do the transaction models agree with the bundled definitions?
 *
 * A model class is little more than a list of field names and the codec type
 * each of them is encoded as. Nothing in the codec checks that list against
 * the definitions: a field named wrongly, or given the wrong type, produces a
 * transaction that encodes into something the network will not accept, and no
 * unit test that only round-trips a hand-written fixture would notice, because
 * the fixture is written from the same wrong list.
 *
 * This test closes that gap. It reads every model in the package, looks each
 * declared field up in definitions/definitions.json and compares the type the
 * definitions give it against the class the model declares. It is therefore
 * also the test that fires when a definitions refresh moves something: the
 * models are checked against whatever set is bundled at the time.
 *
 * The lookup goes through xrpl_php's own public API - Definitions for the
 * field, SerializedType::getTypeByName() for the class - so this package does
 * not carry a second copy of the type table that could drift from the first.
 */
final class DefinitionsConformanceTest extends TestCase
{
    private const MODEL_DIRECTORY = __DIR__ . '/../src/Models/Transaction/TransactionTypes';

    private const MODEL_NAMESPACE = 'Hardcastle\\Xahau_PHP\\Models\\Transaction\\TransactionTypes\\';

    /**
     * Fields that xrpl_php's BaseTransaction declares and Xahau does not know.
     *
     * These are common fields on the XRP Ledger, so the shared base class is
     * right to carry them; they simply have no counterpart here. A model may
     * not use them, and the codec would refuse them, but their presence in the
     * base class is expected rather than a defect - hence an allowlist with a
     * reason instead of a failure.
     *
     * @var array<string, string>
     */
    private const XRPL_ONLY_BASE_FIELDS = [
        'Delegate' => 'Account permission delegation (DelegateSet); the amendment does not exist on Xahau.',
    ];

    /**
     * Every model class this package ships, found on disk rather than listed
     * by hand, so that a new model is covered the moment it is written.
     *
     * @return array<string, array{0: class-string<BaseTransaction>}>
     */
    public static function modelClasses(): array
    {
        $cases = [];
        $files = glob(self::MODEL_DIRECTORY . '/*.php');

        foreach ($files === false ? [] : $files as $file) {
            $shortName = basename($file, '.php');
            /** @var class-string<BaseTransaction> $class */
            $class = self::MODEL_NAMESPACE . $shortName;
            $cases[$shortName] = [$class];
        }

        return $cases;
    }

    /**
     * @param class-string<BaseTransaction> $class
     * @return array<string, string> Field name => declared codec class
     */
    private static function declaredFields(string $class): array
    {
        /** @var array<string, string> $fields */
        $fields = (new ReflectionClass($class))->getDefaultProperties()['transactionTypeProperties'] ?? [];

        return $fields;
    }

    /**
     * @param class-string<BaseTransaction> $class
     */
    #[DataProvider('modelClasses')]
    public function testEveryFieldCarriesTheTypeTheDefinitionsGiveIt(string $class): void
    {
        $definitions = Xahau::definitions();
        $shortName = (new ReflectionClass($class))->getShortName();
        $fields = self::declaredFields($class);

        $this->assertNotEmpty(
            $fields,
            "{$shortName} declares no fields of its own. If the type really has none, say so in a comment; "
            . 'otherwise the field list was forgotten.'
        );

        foreach ($fields as $fieldName => $declaredClass) {
            try {
                $typeName = $definitions->getFieldInstance($fieldName)->getType();
            } catch (Exception) {
                $this->fail(
                    "{$shortName} declares the field {$fieldName}, which the bundled Xahau definitions do not "
                    . 'contain. Either the name is wrong or the definitions predate the field.'
                );
            }

            $this->assertSame(
                get_class(SerializedType::getTypeByName($typeName)),
                $declaredClass,
                "{$shortName}::\${$fieldName} is declared as {$declaredClass}, but the definitions type "
                . "{$fieldName} as {$typeName}."
            );
        }
    }

    /**
     * A model whose class name is not a transaction type cannot ever be built:
     * BaseTransaction::fromArray() compares the class short name against the
     * TransactionType and throws. Catching that here names the culprit.
     *
     * @param class-string<BaseTransaction> $class
     */
    #[DataProvider('modelClasses')]
    public function testTheClassNameIsATransactionTypeXahauKnows(string $class): void
    {
        $shortName = (new ReflectionClass($class))->getShortName();

        $ordinal = Xahau::definitions()->mapSpecificFieldFromValue('TransactionType', $shortName);

        $this->assertIsInt(
            $ordinal,
            "{$shortName} is not a transaction type in the bundled Xahau definitions."
        );
    }

    /**
     * The models are found by scanning a directory. If that scan ever comes up
     * empty - a moved directory, a renamed namespace - the two tests above
     * would pass by doing nothing at all.
     */
    public function testTheModelsAreActuallyBeingFound(): void
    {
        $this->assertGreaterThanOrEqual(
            2,
            count(self::modelClasses()),
            'No models found. The scan in modelClasses() no longer points at the model directory.'
        );
    }

    /**
     * The common fields come from xrpl_php's BaseTransaction and are shared
     * with the XRP Ledger. Where the two networks have drifted, this test says
     * so out loud instead of leaving it to be discovered when the codec throws.
     */
    public function testTheCommonFieldsOfTheSharedBaseClassExistOnXahau(): void
    {
        $definitions = Xahau::definitions();

        /** @var array<string, string> $baseFields */
        $baseFields = (new ReflectionClass(BaseTransaction::class))->getDefaultProperties()['baseProperties'];

        foreach ($baseFields as $fieldName => $declaredClass) {
            if (isset(self::XRPL_ONLY_BASE_FIELDS[$fieldName])) {
                $this->assertFalse(
                    $this->fieldExists($fieldName),
                    "{$fieldName} is allowlisted as XRPL-only, but Xahau's definitions now contain it. "
                    . 'Remove it from XRPL_ONLY_BASE_FIELDS.'
                );

                continue;
            }

            $this->assertTrue(
                $this->fieldExists($fieldName),
                "BaseTransaction declares the common field {$fieldName}, which Xahau does not know. If that is "
                . 'expected, add it to XRPL_ONLY_BASE_FIELDS with the reason.'
            );

            $this->assertSame(
                get_class(SerializedType::getTypeByName($definitions->getFieldInstance($fieldName)->getType())),
                $declaredClass,
                "The common field {$fieldName} has a different type on Xahau than BaseTransaction assumes."
            );
        }
    }

    private function fieldExists(string $fieldName): bool
    {
        try {
            Xahau::definitions()->getFieldInstance($fieldName);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}
