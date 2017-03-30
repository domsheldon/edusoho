<?php

namespace ApiBundle\Api\Resource;

use ApiBundle\Api\Util\UserAssociateUtil;
use Codeages\Biz\Framework\Context\Biz;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Topxia\Service\Common\ServiceKernel;

abstract class Resource
{
    private $logger;

    /**
     * @var Biz
     */
    private $biz;

    /**
     * @var Filter
     */
    private $filter = null;

    const METHOD_SEARCH = 'search';
    const METHOD_GET = 'get';
    const METHOD_ADD = 'add';
    const METHOD_REMOVE = 'remove';
    const METHOD_UPDATE = 'update';
    
    const DEFAULT_PAGING_LIMIT = 10;
    const DEFAULT_PAGING_OFFSET = 0;
    
    public function __construct(Biz $biz)
    {
        $this->biz = $biz;

        $filterClass = $filterClass = get_class($this).'Filter';
        if (class_exists($filterClass)) {
            $this->filter = new $filterClass();
        }
    }

    /**
     * @return Biz
     */
    final protected function getBiz()
    {
        return $this->biz;
    }

    final protected function service($service)
    {
        return $this->getBiz()->service($service);
    }

    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * 检查每个API必需参数的完整性
     */
    protected function checkRequiredFields($requiredFields, $requestData)
    {
        $requestFields = array_keys($requestData);
        foreach ($requiredFields as $field) {
            if (!in_array($field, $requestFields)) {
                throw new \Exception("缺少必需的请求参数{$field}");
            }
        }

        return $requestData;
    }

    protected function guessDeviceFromUserAgent($userAgent)
    {
        $userAgent = strtolower($userAgent);

        $ios = array("iphone", "ipad", "ipod");
        foreach ($ios as $keyword) {
            if (strpos($userAgent, $keyword) > -1) {
                return 'ios';
            }
        }

        if (strpos($userAgent, "Android") > -1) {
            return 'android';
        }

        return 'unknown';
    }

    protected function error($code, $message)
    {
        return array('error' => array(
            'code'    => $code,
            'message' => $message
        ));
    }

    protected function wrap($resources, $total)
    {
        if (is_array($total)) {
            return array('resources' => $resources, 'next' => $total);
        } else {
            return array('resources' => $resources, 'total' => $total ?: 0);
        }
    }

    protected function simpleUsers($users)
    {
        $newArray = array();
        foreach ($users as $key => $user) {
            $newArray[$key] = $this->simpleUser($user);
        }

        return $newArray;
    }

    protected function simpleUser($user)
    {
        $simple = array();

        $simple['id']       = $user['id'];
        $simple['nickname'] = $user['nickname'];
        $simple['title']    = $user['title'];
        $simple['roles']    = $user['roles'];
        $simple['avatar']   = $this->getFileUrl($user['smallAvatar']);

        return $simple;
    }

    protected function filterHtml($text)
    {
        preg_match_all('/\<img.*?src\s*=\s*[\'\"](.*?)[\'\"]/i', $text, $matches);
        if (empty($matches)) {
            return $text;
        }

        foreach ($matches[1] as $url) {
            $text = str_replace($url, $this->getFileUrl($url), $text);
        }

        return $text;
    }

    public function getFileUrl($path)
    {

    }

    public function supportMethods()
    {
        return array(
            static::METHOD_ADD,
            static::METHOD_GET,
            static::METHOD_SEARCH,
            static::METHOD_UPDATE,
            static::METHOD_REMOVE
        );
    }

    /**
     * @return UserAssociateUtil
     */
    public function getUAUtil()
    {
        $biz = $this->getBiz();
        return $biz['api.util.userAssoc'];
    }
    
    protected function makePagingObject($objects, $total, $offset, $limit)
    {
        return array(
            'data' => $objects,
            'paging' => array(
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit
            )
        );
    }

    protected function getCurrentUser()
    {
        $biz = $this->getBiz();
        return $biz['user'];
    }

    protected function addError($logName, $message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $this->getLogger($logName)->error($message);
    }

    protected function addDebug($logName, $message)
    {
        if (!$this->isDebug()) {
            return;
        }
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $this->getLogger($logName)->debug($message);
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    protected function isDebug()
    {
        return 'dev' == $this->getServiceKernel()->getEnvironment();
    }

    protected function getLogger($name)
    {
        if ($this->logger) {
            return $this->logger;
        }

        $this->logger = new Logger($name);
        $this->logger->pushHandler(new StreamHandler($this->biz['kernel.logs_dir'].'/service.log', Logger::DEBUG));

        return $this->logger;
    }
}