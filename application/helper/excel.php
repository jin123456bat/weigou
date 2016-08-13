<?php
namespace application\helper;
class excel
{
	function phpexcel($data,$template,$line_start = 2)
	{
		$phpexcel_root = ROOT.'/extends/PHPExcel';
		include_once $phpexcel_root.'/PHPExcel/IOFactory.php';
		
		$objPHPExcel = \PHPExcel_IOFactory::load($template);
		
		//设置为字符串格式
		/* for($i = 0;$i<count($data[0]);$i++)
		{
			$prefix = ord('A')-1;
			$hasPrefix = false;
			
			$temp = $i;
			while($temp+ord('A') > ord('Z'))
			{
				$temp -= ord('Z') - ord('A') + 1;
				$hasPrefix = true;
				$prefix++;
			}
			$field = ($hasPrefix?chr($prefix):'').chr($temp + ord('A'));
			
			$objPHPExcel->getActiveSheet()->getStyle($field)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		} */
		
		foreach ($data as $index => $value)
		{
			$prefix = ord('A')-1;
			$hasPrefix = false;
			
			for($i=ord('A'),$j=0;$j<count($value);$i++,$j++)
			{
				while ($i>ord('Z'))
				{
					$i -= ord('Z') - ord('A') + 1;
					$hasPrefix = true;
					$prefix++;
				}
				$field = ($hasPrefix?chr($prefix):'').chr($i).($index+$line_start);
				
				if (is_string(current($value)))
				{
					//卧槽 这样居然可以解决科学计数法的问题
					$objPHPExcel->getActiveSheet()->setCellValueExplicit($field,current($value),\PHPExcel_Cell_DataType::TYPE_STRING);
				}
				elseif (is_object(current($value)) && current($value) instanceof \stdClass)
				{
					
					$ActiveSheet = $objPHPExcel->getActiveSheet();
					$ActiveSheet->getCell($field)
					->getDataValidation()
					->setType(current($value)->type)
					->setErrorStyle(current($value)->ErrorStyle)
					->setAllowBlank(current($value)->AllowBlank)
					->setShowInputMessage(current($value)->ShowInputMessage)
					->setShowErrorMessage(current($value)->ShowErrorMessage)
					->setShowDropDown(current($value)->ShowDropDown)
					->setErrorTitle(current($value)->ErrorTitle)
					->setError(current($value)->Error)
					->setPromptTitle(current($value)->PromptTitle)
					->setFormula1(current($value)->Formula1);
					$ActiveSheet->setCellValue($field,current($value)->value);
				}
				next($value);
			}
		}
		
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$filename = time('Y-m-d H:i:s').'.'.pathinfo($template,PATHINFO_EXTENSION);
		$objWriter->save($filename);
		return $filename;
		
	}
}