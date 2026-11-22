<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\BuildTools\PHPStan;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

/**
 * @brief   PHPStan: a record method added by record extensions.
 *
 * Signature of the static method of each extension, without its first parameter
 * (the record, given by MetaRecord::__call()).
 */
class RecordExtensionMethodReflection implements MethodReflection
{
    /**
     * @var null|list<ParametersAcceptor>
     */
    private ?array $variants = null;

    /**
     * @param   list<MethodReflection>  $methods    Static methods of record extensions
     */
    public function __construct(
        private readonly ClassReflection $record,
        private readonly string $name,
        private readonly array $methods,
    ) {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->record;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->methods === [] ? $this->name : $this->methods[0]->getName();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * @return  list<ParametersAcceptor>
     */
    public function getVariants(): array
    {
        if ($this->variants === null) {
            $this->variants = [];
            foreach ($this->methods as $method) {
                foreach ($method->getVariants() as $variant) {
                    $this->variants[] = new FunctionVariant(
                        TemplateTypeMap::createEmpty(),
                        null,
                        array_values(array_slice($variant->getParameters(), 1)),
                        $variant->isVariadic(),
                        $variant->getReturnType()
                    );
                }
            }
        }

        return $this->variants;
    }

    /**
     * Deprecated if every extension method is deprecated.
     */
    public function isDeprecated(): TrinaryLogic
    {
        if ($this->methods === []) {
            return TrinaryLogic::createNo();
        }
        foreach ($this->methods as $method) {
            if (!$method->isDeprecated()->yes()) {
                return TrinaryLogic::createNo();
            }
        }

        return TrinaryLogic::createYes();
    }

    public function getDeprecatedDescription(): ?string
    {
        return $this->isDeprecated()->yes() ? $this->methods[0]->getDeprecatedDescription() : null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}
