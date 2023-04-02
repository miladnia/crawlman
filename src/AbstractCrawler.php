<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman;

use Chakavang\Crawlman\Util\UriUtil;
use Goutte\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractCrawler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TAG = "Crawler - ";
    const LANG_ENGLISH = 'en';
    const LANG_PERSIAN = 'fa';

    /**
     * @var string
     */
    private $url;

    /**
     * @var null|string
     */
    private $langCode;

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var bool
     */
    protected $cacheMode;

    /**
     * @var string
     */
    protected $cacheDirectory;

    public function __construct(string $url, ?string $langCode, bool $cacheMode, string $cacheDirectory, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->cacheMode = $cacheMode;
        $this->cacheDirectory = rtrim($cacheDirectory, '/');

        if (UriUtil::isHttp($url)) {
            if (!$this->matchUrl($url)) {
                throw new \InvalidArgumentException(sprintf("Url %s is not a \"%s\" url.", $url, $this->getCodeName()));
            }
            $this->url = $this->encodeUrl($url);
            $this->langCode = $this->extractUrlLang($url);
        } else {
            $this->url = $this->makeUrl($url, $langCode);
            $this->langCode = $langCode;
        }

        $this->logger->info(self::TAG . '"' . urldecode($url) . '"', [
            "crawler" => $this->getCodeName(),
            "url" => urldecode($this->url),
            "lang" => $this->langCode,
            "cache-mode" => $this->cacheMode
        ]);

        $this->createCrawler(function () {
            $this->crawl(
                $this->getCrawler(),
                UriUtil::exportFragmentIdentifier($this->url));
        });
    }

    /**
     * Replace special chars with percent sign followed by hex digits.
     */
    protected function encodeUrl(string $url): string
    {
        if (UriUtil::isEncoded($url)) {
            return $url;
        }

        return UriUtil::encodeUrlQuery($url, $this->extractUrlQuery($url));
    }

    private function createCrawler(callable $callback): void
    {
        if (!$this->cacheMode) {
            $response = $this->sendHttpRequest();
            if (200 === $response->getStatusCode()) {
                $callback();
            }
            return;
        }

        if ($this->loadCacheFile()
            || $this->writeCacheFile()) {
            $callback();
        }
    }

    private function sendHttpRequest(): Response
    {
        $client = new Client([
            'curl' => [CURLOPT_SSLVERSION => 3],
        ]);
        $this->crawler = $client
            ->request("GET", $this->url);
        $response = $client->getInternalResponse();

        if (200 === $response->getStatusCode()) {
            $this->logger->info(self::TAG . "Successful http request.");
        } else {
            $this->logger->error(self::TAG . "Http request failed.", [
                "status-code" => $response->getStatusCode()
            ]);
        }

        return $response;
    }

    private function loadCacheFile(): bool
    {
        $path = $this->getCachedFilePath();

        if (!file_exists($path)) {
            return false;
        }

        $this->logger->debug(self::TAG . "The cache file found.", [
            "file-path" => $path
        ]);

        $content = file_get_contents($path);

        if (false === $content) {
            $this->logger->error("Could not read the cache file.", [
                "file-path" => $path
            ]);
            return false;
        }

        $this->crawler = new Crawler(null, $this->url);
        $this->crawler->addContent($content);

        return true;
    }

    private function writeCacheFile(): bool
    {
        $this->logger->info(self::TAG . "Writing cache file.");

        $response = $this->sendHttpRequest();

        if (200 !== $response->getStatusCode()) {
            return false;
        }

        $path = $this->getCachedFilePath();
        $bytes = file_put_contents(
            $path, $response->getContent());

        if (false === $bytes) {
            $this->logger->error(self::TAG . "Can not write to file.", [
                "file-path" => $path
            ]);

            return false;
        }

        return true;
    }

    private function getCachedFilePath(): string
    {
        return $this->cacheDirectory
            . '/' . $this->getCodeName()
            . '/' . urldecode($this->extractUrlQuery($this->url))
            . ".html";
    }

    protected function getUrl(): string
    {
        return $this->url;
    }

    protected function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    protected function getLangCode(): string
    {
        return $this->langCode;
    }

    protected function isLang(string $langCode): bool
    {
        return $langCode === $this->langCode;
    }

    protected abstract function crawl(Crawler $crawler, ?string $fragmentId = null): void;

    protected abstract function makeUrl(string $query, ?string $langCode = null): string;

    protected abstract function matchUrl(string $url): bool;

    protected abstract function extractUrlLang(string $url): string;

    protected abstract function extractUrlQuery(string $url): ?string;

    protected abstract function getCodeName(): string;
}
