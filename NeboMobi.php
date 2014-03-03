<?php
//namespace NeboMobi;
define('SITE_LOGIN', '');
define('SITE_PASSWORD', '');

define('CURRENT_DIR', str_replace('//','/',dirname(__FILE__).'/'));
include_once CURRENT_DIR . 'library/simple_html_dom.php';

define('URL_PURCHASE_GOODS', 'http://nebo.mobi/floors/0/2/');
define('URL_LAY_OUT_ITEMS', 'http://nebo.mobi/floors/0/3/');
define('URL_GATHER_EARNINGS', 'http://nebo.mobi/floors/0/5/');
define('URL_LIFT' , 'http://nebo.mobi/lift/');

class NeboMobi {
    protected $login;
    protected $password;
    protected $fileCookies = "cookies\\cookies.txt";
    protected $host = 'http://nebo.mobi/';
    protected $headers = array
    (
        'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.0; uk; rv:1.9.2.3) Gecko/20100401 MRA 5.6 (build 03278) Firefox/3.6.3 ( .NET CLR 3.5.30729)',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7'
    );

    public function __construct($login, $password){
        $this->login = $login;
        $this->password = $password;
    }

    protected function sendGetRequest($url){
        $err=1;
        while ($err) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1); //1-заголовок ответа сервера
            curl_setopt($ch, CURLOPT_NOBODY, 0); //0-скачка страницы
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_REFERER, "");
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCurrentFileCookies());
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCurrentFileCookies());
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $yes = curl_errno($ch);
            if ($yes == 0) $err = 0; else {
                echo 'Bed request' . PHP_EOL;
            }
            curl_close($ch);
            sleep(2);
        }

        return $result;
    }

    protected function getCurrentFileCookies()
    {
        $filePath = CURRENT_DIR . $this->fileCookies;
        if (!file_exists($filePath)) {
            file_put_contents($filePath, '');
        }
        return $filePath;
    }

    protected function sendPostRequest($url, array $postData)
    {
        $err1 = 1;
        while ($err1) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCurrentFileCookies());
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCurrentFileCookies());
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->createPostData($postData));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            $yes = curl_errno($ch);
            if ($yes == 0) $err1 = 0; else {
                echo 'Bed post Request';
            }
            curl_close($ch);
            sleep(2);
        }

        return $result;
    }

    protected function createPostData($data){
        $st = '';
        foreach($data as $key => $val){
            $st .= $key . '=' .$val . '&';
        }

        return trim($st, '&');
    }

    public function isAuthorize(){
        $content = $this->sendGetRequest($this->host);
        $pos = strpos($content, 'footerPanel:logoutLink');
        if ($pos !== false){
            return true;
        }

        return false;
    }

    public function authorize(){
        if ($this->isAuthorize()){
            echo "Already logged" . PHP_EOL;
            return true;
        }

        $contentPageLogin = $this->sendGetRequest($this->host . 'login');
        $html = str_get_html($contentPageLogin);
        if ($formTag = $html->find('form')) {
            if (isset($formTag[0])) {
                $action = $formTag[0]->action;
            }
        }
        unset($html);
        unset($formTag);
        $postParameter = array(
            'login'=>$this->login,
            'password'=>$this->password
        );

        $url = $this->host . 'login' . $action;
        $this->sendPostRequest($url, $postParameter);

        if ($this->isAuthorize()) {
            echo "Logged in" . PHP_EOL;
            return true;
        } else {
            die('Not logged in');
        }
    }

    /*
     * Собрать выручку
     */
    public function gatherEarnings(){
        $count = 0;
        $pageGatherEarnings = $this->sendGetRequest(URL_GATHER_EARNINGS);
        $html = str_get_html($pageGatherEarnings);
        if ($urlEarnings = $html->find('div.flbdy a.tdu')) {
            foreach ($urlEarnings as $url) {
                if (isset($url->href)) {
                    if ($url->innertext == 'Собрать выручку!'){
                        $this->sendGetRequest(URL_GATHER_EARNINGS . $url->href);
                        $count++;
                    }
                }
            }
        }
        unset($html);
        unset($urlEarnings);

        return $count;
    }


    /*
     * Выложить товар
     */
    public function layOutItems(){
        $count = 0;
        $pageLayOutItems = $this->sendGetRequest(URL_LAY_OUT_ITEMS);
        $html = str_get_html($pageLayOutItems);
        if ($urlLayOutItems = $html->find('div.flbdy a.tdu')) {
            foreach ($urlLayOutItems as $url) {
                if (isset($url->href)) {
                    if ($url->innertext == 'Выложить товар') {
                        $this->sendGetRequest(URL_LAY_OUT_ITEMS . $url->href);
                        $count++;
                    }
                }
            }
        }
        unset($html);
        unset($urlLayOutItems);

        return $count;
    }

    /*
     * Закупить товар
     */
    public function purchaseGoods(){
        $count = 0;
        $pagePurchaseGoods = $this->sendGetRequest(URL_PURCHASE_GOODS);
        $html = str_get_html($pagePurchaseGoods);
        if ($urlGatherEarnings = $html->find('div.flbdy a.tdu')) {
            foreach ($urlGatherEarnings as $url) {
                if (isset($url->href)) {
                    if ($url->innertext == 'Закупить товар') {
                        $urlPurchase = URL_PURCHASE_GOODS . $url->href;
                        $pagePurchase = $this->sendGetRequest(URL_PURCHASE_GOODS . $url->href);
                        $this->purchase($pagePurchase, $urlPurchase);
                        $count++;
                    }
                }
            }
        }
        unset($html);

        return $count;
    }

    protected function purchase($content, $url){
        $html = str_get_html($content);
        if ($urlPurchase = $html->find('div.prdst a.tdu')) {
            $urlMaxPurchase = array_pop($urlPurchase);
            if (isset($urlMaxPurchase->href)) {
                $this->sendGetRequest($url . $urlMaxPurchase->href);
                unset($html);
                unset($urlEarnings);

                return true;
            }
        }
        unset($html);
        unset($urlEarnings);

        return false;
    }

    /*
     * Поднять лифт
    */
    public function takeTheLift($url = URL_LIFT)
    {
        $pageTakeTheLift = $this->sendGetRequest($url);
        $html = str_get_html($pageTakeTheLift);
        if ($urlTakeTheLift = $html->find('div.lift a.tdu')) {
            if (isset($urlTakeTheLift[0])) {
                if (isset($urlTakeTheLift[0]->href)) {
                    $url = $urlTakeTheLift[0]->href;
                    $url = str_replace('../../', "", $url);
                    $url = str_replace('../lift/', "", $url);
                    $url = str_replace('lift/', "", $url);

                    echo $url . PHP_EOL;

                    unset($html);
                    unset($urlLayOutItems);
                    $this->takeTheLift(URL_LIFT . $url);
                }
            }
        }
    }



}

$neboMoby = new NeboMobi(SITE_LOGIN, SITE_PASSWORD);
$neboMoby->authorize();

//Собрать выручку!
$countGatherEarnings = $neboMoby->gatherEarnings();
echo '\'' . $countGatherEarnings . "' gathered earnings" . PHP_EOL;
//Выложить товар
$countLayOutItems = $neboMoby->layOutItems();
echo '\'' . $countLayOutItems . "' layed out items" . PHP_EOL;
//Закупить товар
$countPurchaseGoods = $neboMoby->purchaseGoods();
echo '\'' . $countPurchaseGoods . "' purchased goods" . PHP_EOL;
//Поднять лифт
$neboMoby->takeTheLift();
die("end");