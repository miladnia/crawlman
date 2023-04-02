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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractCrawlerFactory
{
    /**
     * @var bool
     */
    protected $cacheMode;

    /**
     * @var string
     */
    protected $cacheDirectory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        bool $cacheMode = false,
        string $cacheDirectory = '',
        LoggerInterface $logger = new NullLogger)
    {
        $this->cacheMode = $cacheMode;
        $this->cacheDirectory = $cacheDirectory;
        $this->logger = $logger;
    }
}
