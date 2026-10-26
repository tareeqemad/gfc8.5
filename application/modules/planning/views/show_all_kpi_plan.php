<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: lanakh
 * Date: 13/03/18
 * Time: 10:27 ص
 */
$MODULE_NAME= 'planning';
$TB_NAME= 'planning';
$isCreate =isset($target_details) && count($target_details)  > 0 ?false:true;

$str_count=0;
//var_dump($details);
//echo $id;
?>


<div class="tb_container">
    <table class="table" id="details_tb1" data-container="container"  align="center">
        <thead>
        <tr>
            <th >#</th>
            <th >اسم المؤشر</th>
			<th >وحدة القياس</th>
			<th >الصيغة</th>
			<th >المستهدف السنوي</th>
			<th >القيمة المحققة</th>
			<th >ادارة المؤشر</th>
        </tr>
        </thead>

        <tbody>
    <?php if(count($target_details) > 0) { // تعديل
            $str_count = 0;
            foreach($target_details as $row) {
                ++$str_count+1


                ?>

                <tr>
                   <td>
				   <?=$str_count;?>
                  </td>
					
                    <td>
					<?=$row['KPI_SER_NAME'];?>
                    </td>
					<td>
					<?=$row['UNIT_NAME'];?>
                    </td>
					<td>
					<?=$row['SCAL_NAME'];?>
                    </td>
					<td>
					<?=$row['KPI_TARGET_YEAR'];?>
                    </td>
					<td>
					<?=$row['KPI_VAL_MONTH'];?>
                    </td>

                    <td>
					<i class="fa fa-trophy fa-2x text-primary" aria-hidden="true" onclick=""></i>
                    </td>


                </tr>
            <?php

            
        }
    }
        ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
            
            </th>
         <th>
            
            </th><th>
            
            </th><th>
            
            </th><th>
            
            </th><th>
            
            </th>
          
        </tr>
          

        </tfoot>
    </table></div>







