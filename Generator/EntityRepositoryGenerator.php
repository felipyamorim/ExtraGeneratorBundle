<?php

namespace Citrax\Bundle\ExtraGeneratorBundle\Generator;


class EntityRepositoryGenerator
{
    protected static $_template =
        '<?php

<namespace>

use Doctrine\ORM\EntityRepository;

/**
 * <className>
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class <className> extends EntityRepository
{
    public function searchBy($criteria = array(), $order = array("<queryAlias>.id"=> "DESC")) {
        $qb = $this->createQueryBuilder("<queryAlias>");

        foreach ($criteria as $field => $value) {
		    if (!$this->getClassMetadata()->hasField($field)) {
                // Make sure we only use existing fields (avoid any injection)
                continue;
            } else if($value === "true" || $value === "false"){
                $qb->andWhere($qb->expr()->eq("<queryAlias>." . $field, ":<queryAlias>_" . $field))
                    ->setParameter(":<queryAlias>_" . $field, filter_var($value , FILTER_VALIDATE_BOOLEAN));
            } else {
                $qb->andWhere($qb->expr()->like("<queryAlias>." . $field, ":<queryAlias>_" . $field))
                    ->setParameter(":<queryAlias>_" . $field, "%" . $value . "%");
            }
        }

        foreach ($order as $key => $value) {
            $qb->orderBy($key, $value);
        }

        return $qb->getQuery();
    }
}
';

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    public function generateEntityRepositoryClass($fullClassName)
    {
        $className = substr($fullClassName, strrpos($fullClassName, '\\') + 1, strlen($fullClassName));

        $variables = array(
            '<namespace>' => $this->generateEntityRepositoryNamespace($fullClassName),
            '<className>' => $className,
            '<queryAlias>' => strtolower(substr($className,0,1))
        );

        return str_replace(array_keys($variables), array_values($variables), self::$_template);
    }

    /**
     * Generates the namespace statement, if class do not have namespace, return empty string instead.
     *
     * @param string $fullClassName The full repository class name.
     *
     * @return string $namespace
     */
    private function generateEntityRepositoryNamespace($fullClassName)
    {
        $namespace = substr($fullClassName, 0, strrpos($fullClassName, '\\'));

        return $namespace ? 'namespace ' . $namespace . ';' : '';
    }

    /**
     * @param string $fullClassName
     * @param string $outputDirectory
     *
     * @return void
     */
    public function writeEntityRepositoryClass($fullClassName, $outputDirectory)
    {
        $code = $this->generateEntityRepositoryClass($fullClassName);

        $path = $outputDirectory . DIRECTORY_SEPARATOR
            . str_replace('\\', \DIRECTORY_SEPARATOR, $fullClassName) . '.php';
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if ( ! file_exists($path)) {
            file_put_contents($path, $code);
        }
    }
}
