<?php
/*
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
*/


//$basepathtoroot="/home/b/bestsal3ru/drova-barnaul.ru/public_html/";
$basepathtoroot = __DIR__;
require_once $basepathtoroot."/../ololoshka_config.php";
require_once $basepathtoroot."/SendMailSmtpClass.php";
require_once $basepathtoroot."/sms.ru.php";

/*
require_once $basepathtoroot."ololoshka_config.php";
require_once $basepathtoroot.$basepathsender."/SendMailSmtpClass.php";
require_once $basepathtoroot.$basepathsender."/sms.ru.php";
*/

function phonecut($str) 
    {
        $str = str_replace("-","",$str);
        $str = str_replace("(","",$str);
        $str = str_replace(")","",$str);
        $str = str_replace(" ","",$str);
        return $str;
    }


function translit($s) {
    $s = (string) $s; // преобразуем в строковое значение
    $s = strip_tags($s); // убираем HTML-теги
    $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
    $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
    $s = trim($s); // убираем пробелы в начале и конце строки
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
    $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sh','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
    $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
    //$s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
    return $s; // возвращаем результат
}


$filelist = glob($basepathtoroot."/tmp/*.json");


if ($filelist[0] != ""){
    
    $str = file_get_contents($filelist[0]);
    $dataJ = json_decode($str);


    //отправляем смс
    if (isset($SMSphone) && isset($SMSAPIKey) && isset($SMSName)){

        $messagetxt=''.translit($dataJ->name); 
        $messagetxt.='\n'.translit(phonecut($dataJ->phone)); // 
        
        
        /*
        echo strlen($messagetxt).'<br/>';
        echo $messagetxt;
        echo '<br/>';
        echo '<br/>';
        */
        
        $messagetxt = mb_substr($messagetxt,0,159,'UTF-8');

        $smsru = new SMSRU($SMSAPIKey);
        $SMSdata = new stdClass();
        $SMSdata->to = $SMSphone;
        $SMSdata->text = $messagetxt; 
        $SMSdata->translit = 1;
        //$SMSdata->test = 1; // Позволяет выполнить запрос в тестовом режиме без реальной отправки сообщения
        
        $sms = $smsru->send_one($SMSdata); // Отправка сообщения и возврат данных в переменную
        //var_dump($sms);
    }
    

/*
    $dataJ->title
    $dataJ->name
    $dataJ->phone
    $dataJ->time
    $dataJ->volume
    $dataJ->mera
    $dataJ->price
    $dataJ->priced
    $dataJ->cena
    $dataJ->cenad
    $dataJ->server
*/
// тут собираем мыло

$messagetxt="<p><strong>Сайт:</strong> ".$emailsitename;
$messagetxt.="</p><p><strong>Имя:</strong> ".$dataJ->name;
$messagetxt.="</p><p><strong>Телефон:</strong> <a href='tel:".phonecut($dataJ->phone)."' target='_blank'>".$dataJ->phone."</a>";
$messagetxt.="</p><p><strong>Дата время заказа: </strong>".$dataJ->time;
$messagetxt.="</p><p><strong>ip адрес:</strong> <a href='https://2ip.ru/geoip/?ip=".$dataJ->server."' target='_blank'>".$dataJ->server."</a>";
if (isset($dataJ->recaptcha)){
    $messagetxt.="</p><p><strong>reCAPTCHA:</strong> ".$dataJ->recaptcha->success." | ".$dataJ->recaptcha->challenge_ts." | score = ".$dataJ->recaptcha->score."</p>";
}else{
    $messagetxt.="</p><p><strong>reCAPTCHA:</strong> Отключена</p>";
}

//var_dump($dataJ->recaptcha);


if (isset($SMSphone) && isset($SMSAPIKey) && isset($SMSName)){
    //var_dump($sms);
    $messagetxt.="<br /><br /><p>--- Информация об СМС ---</p>";
    if ($sms->status == "OK") { // Запрос выполнен успешно
        $messagetxt.= "<p>Сообщение отправлено успешно.</p>";
        $messagetxt.= "<p>ID сообщения: ".$sms->sms_id."</p>";
        //$messagetxt.= "<p>Ваш новый баланс: <strong>".$sms->balance." рублей </strong></p>";
        $messagetxt.= "<p>Потрачено: <strong>".$sms->cost." рублей </strong></p>";
        $messagetxt.= "<p>Ответ сервера: ".$sms->status_text."</p>";
    } 
    else {
        $messagetxt.= "<p>Сообщение не отправлено!</p>";
        $messagetxt.= "<p>Код ошибки: <strong>".$sms->status_code."</strong></p>";
        $messagetxt.= "<p>Текст ошибки: ".$sms->status_text."</p>";
        //$messagetxt.= "<p>Ваш новый баланс: <strong>".$sms->balance." рублей </strong></p>";
        $messagetxt.= "<p>Потрачено: <strong>".$sms->cost." рублей </strong></p>";
        
    }
}

//echo $messagetxt;

$mailSMTP = new SendMailSmtpClass($emailsite, $emailpass, 'ssl://smtp.yandex.ru', $emailsitename, 465);

// $mailSMTP = new SendMailSmtpClass('логин', 'пароль', 'хост', 'имя отправителя');

  

// заголовок письма

$headers= "MIME-Version: 1.0\r\n";

$headers .= "Content-type: text/html; charset=utf-8\r\n"; // кодировка письма

$headers .= "From: ".$emailsitename." <".$emailsite.">\r\n"; // от кого письмо

$result =  $mailSMTP->send($emailsend, 'Заявка с сайта '.$dataJ->name.' '.$dataJ->title, $messagetxt, $headers); // отправляем письмо

// $result =  $mailSMTP->send('Кому письмо', 'Тема письма', 'Текст письма', 'Заголовки письма');













if($result === true){

    //Письмо успешно отправлено
    $current = file_get_contents($basepathtoroot."/zakazi.json");

    // читаем файл

    $current .= ",".$str."\n";

    // Пишем содержимое обратно в файл

    file_put_contents($basepathtoroot."/zakazi.json", $current);

    unlink($filelist[0]);

}

//echo $messagetxt;




}