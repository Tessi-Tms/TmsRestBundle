<?php

namespace Tms\Bundle\RestBundle\Binder;

use Doctrine\Common\Util\Inflector;

class DefaultBinder implements BinderInterface
{
    /**
     * {@inheritdoc}
     */
    public function bind(& $object, array $data)
    {
        $rc = new \ReflectionClass($object);
        foreach ($data as $key => $value) {
            $setter =  sprintf("set%s", Inflector::classify($key));
            if ($rc->hasMethod($setter)) {
                call_user_func_array(
                    array($object, $setter),
                    array($value)
                );
            }
        }
    }
}
