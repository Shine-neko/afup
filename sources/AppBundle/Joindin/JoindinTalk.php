<?php

namespace AppBundle\Joindin;

use AppBundle\Event\Model\Talk;
use Psr\Cache\CacheItemPoolInterface;

class JoindinTalk
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }


    public function getStubFromTalk(Talk $talk)
    {
        if (!$talk->hasJoindinId()) {
            return null;
        }

        try {
            return $this->prepareStubFromJoindinResponse($this->callJoindInApi($talk->getJoindinId()));
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * @param int $joindinId
     *
     * @return string
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function callJoindInApi($joindinId)
    {
        $joindInurl = 'http://api.joind.in/v2.1/talks/' . $joindinId . '';
        $cacheItem = $this->cache->getItem(urlencode($joindInurl));
        if (!$cacheItem->isHit()) {
            $cacheItem->set(file_get_contents($joindInurl));
            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    /**
     * @param string $response
     *
     * @return string|null
     *
     * @throws \Exception
     */
    private function prepareStubFromJoindinResponse($response)
    {
        $decodedResponse = $this->readResponse($response);

        if (!isset($decodedResponse['talks'][0]['stub'])) {
            return null;
        }

        return $decodedResponse['talks'][0]['stub'];
    }

    /**
     * @param string $response
     *
     * @return array
     */
    private function readResponse($response)
    {
        $decodedResponse = json_decode($response, true);
        if (!is_array($decodedResponse) || !isset($decodedResponse['talks'])) {
            throw new \RuntimeException('Error reading joindin response');
        }

        return $decodedResponse;
    }
}
