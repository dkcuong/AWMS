<?php

namespace tables;

class seldatContainers extends \tables\_default
{

    function displayInput($post, $params)
    {
        $name = $params['name'];
        $class = $params['class'];
        $size = getDefault($params['size'], 7);
        $style = getDefault($params['style']);
        $readOnly = getDefault($params['readOnly']);

        $value = getDefault($post[$name], NULL);
        $style = $style ? 'style="'.$style.'"' : NULL;
        $readOnly = $readOnly ? ' readonly' : NULL; ?>

        <td>
        <input type="text" class="<?php echo $class; ?>"
               size="<?php echo $size; ?>" <?php echo $style . $readOnly; ?>
               name="<?php echo $name; ?>" value="<?php echo $value; ?>">
        </td><?php
    }
    /*
    ****************************************************************************
    */


}