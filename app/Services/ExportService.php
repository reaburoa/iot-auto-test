<?php

namespace App\Services;

use App\Core\Service;
use PHPExcel;
use PHPExcel_IOFactory;

class ExportService extends Service
{
    public function export($data, $model)
    {
        $PHPExcel = new PHPExcel();
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle('汇总表');

        $this->CreateBlock($PHPSheet, "A1:J2", "IOT自动化测试报告", "5B9BD5");
        $this->CreateBlock($PHPSheet, "A3:A5", "测试轮次", "808080");
        $this->CreateBlock($PHPSheet, "B3:B5", "总共测试机器(台)", "808080");
        $this->CreateBlock($PHPSheet, "C3:F3", "测试次数", "808080");
        $this->CreateBlock($PHPSheet, "C4:D4", "成功(台)", "C4D79B");
        $this->CreateBlock($PHPSheet, "E4:F4", "失败(台)", "FABF8F");
        $this->CreateBlock($PHPSheet, "C5", "大后台Bin包升级", "C4D79B");
        $this->CreateBlock($PHPSheet, "D5", "合作伙伴配置下发", "C4D79B");
        $this->CreateBlock($PHPSheet, "E5", "大后台Bin包升级", "FABF8F");
        $this->CreateBlock($PHPSheet, "F5", "合作伙伴配置下发", "FABF8F");
        $this->CreateBlock($PHPSheet, "G3:I4", "测试耗时/分钟", "808080");
        $this->CreateBlock($PHPSheet, "G5", "最长耗时", "D0CECE");
        $this->CreateBlock($PHPSheet, "H5", "最短耗时", "D0CECE");
        $this->CreateBlock($PHPSheet, "I5", "平均耗时", "D0CECE");
        $this->CreateBlock($PHPSheet, "J3:J5", "最后保留机器(台)", "808080");

        for ($i = 65; $i <= 90; $i++) {
            $PHPSheet->getColumnDimension(chr($i))->setWidth(18);
        }
        $this->CalculateRound($PHPSheet, $data);
        $PHPExcel->createSheet();
        $PHPExcel->setActiveSheetIndex(1);
        $PHPSheet = $PHPExcel->getActiveSheet();
        $PHPSheet->setTitle('详情表');
        $this->CalculateAll($PHPSheet, $data);
        for ($i = 65; $i <= 90; $i++) {
            $PHPSheet->getColumnDimension(chr($i))->setWidth(18);
        }

        $PHPExcel->setActiveSheetIndex(0);

        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $ar_filename = [
            ScanBoxService::MODEL => "小闪自动化升级测试报告(" . date("Y-m-d") . ").xlsx",
            MarioService::MODEL => "码利奥自动化升级测试报告(" . date("Y-m-d") . ").xlsx",
        ];
        $filename = !isset($ar_filename[$model]) ? "自动化升级测试报告(" . date("Y-m-d") . ").xlsx" : $ar_filename[$model];
        $PHPWriter->save($filename);
        /*header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
        header('Content-Disposition: attachment;filename="' . $filename . '"');  //告诉浏览器将输出文件的名称
        header('Cache-Control: max-age=0');  //禁止缓存
        $PHPWriter->save('php://output');*/
    }

    private function CreateBlock(&$PHPSheet, $Block, $Text, $BackColor = "")
    {
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,   //设置border样式
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,  另一种样式
                    'color' => array('argb' => 'FF000000'),          //设置border颜色
                ),
            ),
        );
        if (strpos($Block, ":") !== false) {
            list($Start, $End) = explode(":", $Block);
            $PHPSheet->mergeCells("$Start:$End");
        } else {
            $Start = $Block;
        }
        $PHPSheet->setCellValue($Start, $Text);
        $PHPSheet->getStyle($Start)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $PHPSheet->getStyle($Start)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        if ($BackColor != "") {
            $PHPSheet->getStyle($Start)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $PHPSheet->getStyle($Start)->getFill()->getStartColor()->setARGB($BackColor);
        }
        $PHPSheet->getStyle($Block)->applyFromArray($styleThinBlackBorderOutline);
    }

    private function CreateHead(&$PHPSheet, $StartLine, $Round, $time)
    {
        $this->CreateBlock($PHPSheet, "A{$StartLine}:I" . ($StartLine + 1), "第{$Round}轮测试(" . date("Y/m/d", $time) . ")", "5B9BD5");
        $StartLine += 2;
        $this->CreateBlock($PHPSheet, "A{$StartLine}", "SN", "D0CECE");
        $this->CreateBlock($PHPSheet, "B{$StartLine}", "开始时间", "D0CECE");
        $this->CreateBlock($PHPSheet, "C{$StartLine}", "结束时间", "D0CECE");
        $this->CreateBlock($PHPSheet, "D{$StartLine}", "耗时", "D0CECE");
        $this->CreateBlock($PHPSheet, "E{$StartLine}:F{$StartLine}", "升级是否成功", "D0CECE");
        $this->CreateBlock($PHPSheet, "G{$StartLine}:H{$StartLine}", "配置下发是否成功", "D0CECE");
        $this->CreateBlock($PHPSheet, "I{$StartLine}", "异常情况分析", "D0CECE");
        return 3;
    }

    private function CalculateRound(&$PHPSheet, $turn_data)
    {
        foreach ($turn_data as $key => $value) {
            $firmware_ok_total = 0;
            $firmware_fail_total = 0;
            $setting_ok_total = 0;
            $setting_failed_total = 0;
            $all_time = 0;
            $max_time = 0;
            $min_time = 0;
            foreach ($value as $machine) {
                if ($machine['firmware_upgrade_state'] == 1) {
                    $firmware_ok_total++;
                } else {
                    $firmware_fail_total++;
                }
                // todo 小闪配置
                $start_time = $machine['firmware_upgrade_start'] != '0000-00-00 00:00:00' ? strtotime($machine['firmware_upgrade_start']) : 0;
                $end_time = $machine['firmware_upgrade_end'] != '0000-00-00 00:00:00' ? strtotime($machine['firmware_upgrade_end']) : 0;
                if ($start_time != 0 && $end_time != 0) {
                    $time = $end_time - $start_time;
                    $all_time += $time;
                    $min_time = $time;
                    $max_time = $max_time < $time ? $time : $max_time;
                    $min_time = $min_time < $time ? $min_time : $time;
                }
            }
            $avg_time = 0;
            if ($all_time > 0) {
                $avg_time = $firmware_ok_total == 0 ? 0 : $all_time / $firmware_ok_total;
            }
            $this->CreateBlock($PHPSheet, "A" . ($key + 5), $key);
            $this->CreateBlock($PHPSheet, "B" . ($key + 5), count($value));
            $this->CreateBlock($PHPSheet, "C" . ($key + 5), $firmware_ok_total);
            $this->CreateBlock($PHPSheet, "D" . ($key + 5), 0);
            $this->CreateBlock($PHPSheet, "E" . ($key + 5), $firmware_fail_total);
            $this->CreateBlock($PHPSheet, "F" . ($key + 5), 0);
            $this->CreateBlock($PHPSheet, "G" . ($key + 5), $this->getTakeTime($max_time));
            $this->CreateBlock($PHPSheet, "H" . ($key + 5), $this->getTakeTime($min_time));
            $this->CreateBlock($PHPSheet, "I" . ($key + 5), $this->getTakeTime($avg_time));
            $this->CreateBlock($PHPSheet, "J" . ($key + 5), $firmware_ok_total);
        }
    }

    private function CalculateAll(&$PHPSheet, $turn_data)
    {
        $StartLine = 1;
        foreach ($turn_data as $key => $value) {
            $StartLine += $this->CreateHead($PHPSheet, $StartLine, $key, strtotime($value[0]['created_at']));
            foreach ($value as $machine) {
                $start_time = $machine['firmware_upgrade_start'] == '0000-00-00 00:00:00' ? 0 : strtotime($machine['firmware_upgrade_start']);
                $end_time = $machine['firmware_upgrade_end'] == '0000-00-00 00:00:00' ? 0 : strtotime($machine['firmware_upgrade_end']);
                $take_time = $end_time != 0 ? $end_time - $start_time : 0;
                $upgrade_ret = '否';
                $err_desc = $machine['firmware_download_state'] == -1 ? '设备下载长时间未响应' : '未知';
                if ($machine['firmware_upgrade_state'] == 1) {
                    $upgrade_ret = "是";
                    $err_desc = "/";
                }
                // todo 小闪配置
                $this->CreateBlock($PHPSheet, "A{$StartLine}", $machine['msn']);
                $this->CreateBlock($PHPSheet, "B{$StartLine}", date("H:i:s", $start_time));
                $this->CreateBlock($PHPSheet, "C{$StartLine}", $end_time == 0 ? "/" : date("H:i:s", $end_time));
                $this->CreateBlock($PHPSheet, "D{$StartLine}", $this->getTakeTime($take_time));
                $this->CreateBlock($PHPSheet, "E{$StartLine}:F{$StartLine}", $upgrade_ret);
                $this->CreateBlock($PHPSheet, "G{$StartLine}:H{$StartLine}", '');
                $this->CreateBlock($PHPSheet, "I{$StartLine}", $err_desc);
                $StartLine++;
            }
        }
    }

    private function getTakeTime($time_diff)
    {
        if ($time_diff == 0) {
            return "/";
        }
        $days = intval($time_diff / 86400);
        $remain = $time_diff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        return "{$hours}时{$mins}分{$secs}秒";
    }
}
