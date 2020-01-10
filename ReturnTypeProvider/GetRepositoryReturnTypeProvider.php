<?php

declare(strict_types=1);

namespace RunToTheFather\ReturnTypeProvider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping\Entity;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Union;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GetRepositoryReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array
    {
        return [
            ObjectManager::class,
            RegistryInterface::class,
            AbstractManagerRegistry::class,
            ManagerRegistry::class,
        ];
    }

    /**
     * @param StatementsSource $source
     * @param string $fq_classlike_name
     * @param string $method_name_lowercase
     * @param array<\PhpParser\Node\Arg> $call_args
     * @param Context $context
     * @param CodeLocation $code_location
     * @param array<\Psalm\Type\Union>|null $template_type_parameters
     *
     * @param string|null $called_fq_classlike_name
     * @param string|null $called_method_name_lowercase
     *
     * @return \Psalm\Type\Union|null
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        array $template_type_parameters = null,
        string $called_fq_classlike_name = null,
        string $called_method_name_lowercase = null
    ) {
        AnnotationRegistry::registerLoader('class_exists');
        $reader = new AnnotationReader();

        /** @var Union|null $inferred */
        $inferred = $call_args[0]->value->inferredType ?? null;

        $returns = [];

        if (null !== $inferred && 'getrepository' === $method_name_lowercase) {
            foreach ($inferred->getAtomicTypes() as $type) {
                if ($type instanceof TLiteralClassString) {
                    $clz = $type->value;
                    /** @var Entity|null $annot */
                    $annot = $reader->getClassAnnotation(new \ReflectionClass($clz), Entity::class);

                    if (null !== $annot && null !== $annot->repositoryClass) {
                        $returns[] = $annot->repositoryClass;
                    } else {
                        $returns[] = ObjectRepository::class;
                    }
                }
            }

            if (0 === \count($returns)) {
                return Type::parseString(ObjectRepository::class);
            }

            \array_unique($returns);

            $project_analyzer = ProjectAnalyzer::getInstance();
            $codebase = $project_analyzer->getCodebase();

            foreach ($returns as $return) {
                $codebase->classlikes->addFullyQualifiedClassName($return);
            }

            return Type::parseString(\implode('|', $returns));
        }

        return null;
    }
}
