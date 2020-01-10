# doctrine-psalm-plugin

By default psalm doesn't recognize return type 
for cases like this

`$object->getRepository(Entity::class)`

where $object can be instance of 

> Doctrine\Common\Persistence\ObjectManager  
> Symfony\Bridge\Doctrine\RegistryInterface  
> Doctrine\Common\Persistence\AbstractManagerRegistry  
> Doctrine\Common\Persistence\ManagerRegistry  

repository class from `@ORM\Entity(repositoryClass="Doctrine\Common\Persistence\ObjectRepository")`
will be returned

## Usage

Add into your psalm.xml file into `<plugins>` section line

`<plugin filename="vendor/runtothefather/doctrine-psalm-plugin/src/ReturnTypeProvider/GetRepositoryReturnTypeProvider.php"></plugin>`