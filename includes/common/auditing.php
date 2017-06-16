<?php

namespace common;

class auditing
{
    const INSERT = 'i';
    const UPDATE = 'u';
    const DELETE = 'd';

    /*
    ****************************************************************************
    */

    static function getData($fieldList, $valueList, $type='insert')
    {
        $status = strtolower($type);

        $fieldList[] = 'status';

        if ($status == 'insert') {
            $valueList[] = self::INSERT;
            $fieldList[] = 'create_by';
        } else {
            $valueList[] = $status == 'delete' ? self::DELETE : self::UPDATE;
            $fieldList[] = 'update_by';
        }

        $valueList[] = \access::getUserID();

        foreach ($fieldList as &$field) {
            $field .= ' = ?';
        }

        return [
            'fieldList' => $fieldList,
            'valueList' => $valueList,
        ];
    }

    /*
    ****************************************************************************
    */

}
