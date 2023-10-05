<?php
/*
 * @author Махнев С.А.
 * @date 05.10.2023
 * @description Парсер html страницы (на входе url или файл), который на выходе будет отображать количество и название всех используемых html тегов
 */

// Инкапсуляция
class Tag {
    private $name;
    private $count;

    public function __construct($name) {
        $this->name = $name;
        $this->count = 1;
    }

    public function increment() {
        $this->count++;
    }

    public function getName() {
        return $this->name;
    }

    public function getCount() {
        return $this->count;
    }
}

// Полиморфизм
interface ContentFetcher {
    public function fetch($source);
}

class HttpRequest implements ContentFetcher {
    //на случай url без указания протокола
    private function prepareUrl($url) {
        if (!preg_match("~^https?://~i", $url)) {
            return "http://" . $url;
        }
        return $url;
    }

    //для вылавливания ошибки
    private function executeCurlRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Ошибка cURL: ' . curl_error($ch));
        }
        curl_close($ch);
        return $data;
    }

    public function fetch($url) {
        $url = $this->prepareUrl($url);
        return $this->executeCurlRequest($url);
    }
}

class FileRequest implements ContentFetcher {
    public function fetch($filePath) {
        return file_get_contents($filePath);
    }
}

// Абстракция
abstract class BaseParser {
    protected $tags = [];

    abstract public function parse($htmlContent);

    public function getTags() {
        return $this->tags;
    }
}

class Parser extends BaseParser {
    //Простой парсер
    public function parse($htmlContent) {
        preg_match_all('/<\s*([^\/!>\s]+)[^>]*>/', $htmlContent, $matches);
        foreach ($matches[1] as $match) {
            $tagName = strtolower($match);
            if (isset($this->tags[$tagName])) {
                $this->tags[$tagName]->increment();
            } else {
                $this->tags[$tagName] = new Tag($tagName);
            }
        }
    }
    //Более строгий парсер
    public function parseDiff($htmlContent) {
        preg_match_all('/<\s*([a-zA-Z0-9]+)[\s>]/', $htmlContent, $matches);

        $validTags = [
            'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base', 'bdi', 'bdo', 'blockquote',
            'body', 'br', 'button', 'canvas', 'caption', 'cite', 'code', 'col', 'colgroup', 'data', 'datalist',
            'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'figcaption',
            'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html', 'i',
            'iframe', 'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'link', 'main', 'map', 'mark', 'meta',
            'meter', 'nav', 'noscript', 'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'picture',
            'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'script', 'section', 'select', 'small', 'source',
            'span', 'strong', 'style', 'sub', 'summary', 'sup', 'svg', 'table', 'tbody', 'td', 'template', 'textarea',
            'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video', 'wbr'
        ];

        foreach ($matches[1] as $match) {
            $tagName = strtolower($match);
            if (in_array($tagName, $validTags)) {
                if (isset($this->tags[$tagName])) {
                    $this->tags[$tagName]->increment();
                } else {
                    $this->tags[$tagName] = new Tag($tagName);
                }
            }
        }
    }
}


$source = "irgups.ru";
$fetcher = new HttpRequest();

//$fetcher = new FileRequest(); //для чтения из файла
$htmlContent = $fetcher->fetch($source);

$parser = new Parser();
$parser->parse($htmlContent);
//$parser->parseDiff($htmlContent); //исключает ошибочные вхождения

foreach ($parser->getTags() as $tag) {
    echo "Tag: " . $tag->getName() . " - Count: " . $tag->getCount() . "\n";
}

?>
