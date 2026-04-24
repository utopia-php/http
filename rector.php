<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        // BC breaks in a published library
        ReadOnlyPropertyRector::class,
        ReadOnlyClassRector::class,

        // Changes truthy semantics — "0", null, "" behave differently
        ExplicitBoolCompareRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,

        // Can throw TypeError on previously-working callers (library is public API)
        TypedPropertyFromAssignsRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        ParamTypeByParentCallTypeRector::class,

        // Different distribution and failure mode than rand()
        RandomFunctionRector::class,

        // Subtle casting/control-flow shifts — apply manually
        RecastingRemovalRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        SwitchNegatedTernaryRector::class,
        StringClassNameToClassConstantRector::class,

        // Promoted properties / nullable defaults — BC shape changes for library
        ClassPropertyAssignToConstructorPromotionRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,

        // empty() replacement rarely covers every falsy case Rector's type info misses
        DisallowedEmptyRuleFixerRector::class,

        // Throws TypeError when args are objects/arrays — review per-call
        NullToStrictStringFuncCallArgRector::class,
    ]);
