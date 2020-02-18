<?php

declare(strict_types=1);

namespace RunToTheFather\ReturnTypeProvider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\AbstractManagerRegistry as AbstractManagerRegistryAlias;
use Doctrine\Common\Persistence\ManagerRegistry as ManagerRegistryAlias;
use Doctrine\Common\Persistence\ObjectManager as ObjectManagerAlias;
use Doctrine\Common\Persistence\ObjectRepository as ObjectRepositoryAlias;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping\Entity;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;
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
            AbstractManagerRegistryAlias::class,
            ManagerRegistryAlias::class,
            ObjectManagerAlias::class,
            ObjectRepositoryAlias::class
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

        $entityFqcn = null;
        $class = null;

        if (isset($call_args[0])
            && is_object($call_args[0])
            && is_object($call_args[0]->value)
            && property_exists($call_args[0]->value, 'class')
        ) {
            /* @var PhpParser\Node\Name */
            $class = $call_args[0]->value->class;
        }

        if (null !== $class) {
            $entityFqcn = $class->getAttribute('resolvedName') ?? null;
        }

        $return = null;

        if (null !== $entityFqcn && 'getrepository' === $method_name_lowercase) {
            /** @var Entity|null $annot */
            $annot = $reader->getClassAnnotation(new \ReflectionClass($entityFqcn), Entity::class);

            if (null !== $annot && null !== $annot->repositoryClass) {
                $return = $annot->repositoryClass;
            } else {
                $return = ObjectRepository::class;
            }

            if (null === $return) {
                return Type::parseString(ObjectRepository::class);
            }

            $project_analyzer = ProjectAnalyzer::getInstance();
            $codebase = $project_analyzer->getCodebase();

            $codebase->classlikes->addFullyQualifiedClassName($return);

            return Type::parseString($return);
        }

        return null;
    }
}
