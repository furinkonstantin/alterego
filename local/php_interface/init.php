<?php

    function ParsingRss() {
        
        if (!CModule::IncludeModule("iblock")) {
            return false;
        }
        
        $arFields = array();
        $iblock_id = 3;
        
        $url = "http://k.img.com.ua/rss/ru/all_news2.0.xml";
        $xml = xml_parser_create();
        xml_parser_set_option($xml, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($xml, file_get_contents($url), $element, $index);
        xml_parser_free($xml);
        $count = count($index["TITLE"])-1;
        for ($i = 1; $i < $count; $i++) {
            $title = $element[$index["TITLE"][$i+1]]["value"];
            $description = $element[$index["DESCRIPTION"][$i+1]]["value"];
            $link = $element[$index["LINK"][$i+1]]["value"];
            $xml_id = md5($link);
            $picture = $element[$index["ENCLOSURE"][$i+1]]["attributes"]["URL"];
            $category = $element[$index["CATEGORY"][$i+1]]["value"];
            if (!IssetNewByXMLID($iblock_id, $xml_id)) {
                $sectionId = IssetAndMakeCategoryByName($iblock_id, $category);
                $arFields = array(
                    "IBLOCK_ID" => $iblock_id,
                    "IBLOCK_SECTION_ID" => $sectionId,
                    "NAME" => $title,
                    "ACTIVE" => "Y",
                    "DETAIL_TEXT" => $description,
                    "DETAIL_TEXT_TYPE" => "html",
                    "CODE" => CUtil::translit($title, "ru"),
                    "XML_ID" => $xml_id
                );
                if (!empty($picture)) {
                    $arFields["PREVIEW_PICTURE"] = CFile::MakeFileArray($picture);
                }
                $el = new CIBlockElement;
                $el->Add($arFields);
            }
        }
        
        $headers = get_headers($url);
        $headers = "HTTP-заголовок: " . PHP_EOL . implode(PHP_EOL, $headers);
        $time = "Время последнего запроса: " . date("d.m.Y H:i:s");
        $text = 
            PHP_EOL .
            "--------------------------------------------------------" .
            PHP_EOL .
            $time .
            PHP_EOL .
            $headers .
            PHP_EOL .
            "--------------------------------------------------------" .
            PHP_EOL;
        $file = fopen(__DIR__ . "/parsing_history.txt", "a+");
        fwrite($file, $text);
        fclose($file);
        return "ParsingRss();";
    }
    
    function IssetAndMakeCategoryByName($iblock_id, $category) {
        $res = 0;
        $issetSection = CIBlockSection::GetList(array(), 
            array(
                "IBLOCK_ID" => $iblock_id, 
                "NAME" => $category
            )
        )->Fetch();
        if (!$issetSection) {
            $section = new CIBlockSection;
            $arFields = array(
                "IBLOCK_ID" => $iblock_id,
                "ACTIVE" => "Y",
                "NAME" => $category,
                "CODE" => CUtil::translit($category, "ru")
            );
            $res = $section->Add($arFields);
        } else {
            $res = $issetSection["ID"];
        }
        return $res;
    }
    
    function IssetNewByXMLID($iblock_id, $xml_id) {
        $res = false;
        $issetElement = CIBlockElement::GetList(array(), 
            array(
                "IBLOCK_ID" => $iblock_id, 
                "XML_ID" => $xml_id
            )
        )->Fetch();
        if ($issetElement) {
            $res = true;
        }
        return $res;
    }