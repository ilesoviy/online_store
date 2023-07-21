<?php
namespace service\cron;
use Closure;
use Cron\CronExpression;
use Jenssegers\Date\Date;
use think\Cache;
//Task抽象类
abstract class Task
{
    use ManagesFrequencies;
    /** @var \DateTimeZone|string 时区 */
    public $timezone;
    /** @var string 任务周期 */
    public $expression = '* * * * * *';
    /** @var bool 任务是否可以重叠执行 */
    public $withoutOverlapping = false;
    /** @var int 最大执行时间(重叠执行检查用) */
    public $expiresAt = 1440;
    /** @var bool 分布式部署 是否仅在一台服务器上运行 */
    public $onOneServer = false;
    protected $filters = [];
    protected $rejects = [];
    public function __construct()
    {
        $this->configure();
    }
    /**
     * 是否到期执行
     * @return bool
     */
    public function isDue()
    {
        $date = Date::now($this->timezone);
        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }
    /**
     * 配置任务
     */
    protected function configure()
    {
    }
    /**
     * 执行任务
     * @return mixed
     */
    abstract protected function execute();
    final public function run()
    {
        if ($this->withoutOverlapping &&
            !$this->createMutex()) {
            return;
        }
        register_shutdown_function(function () {
            $this->removeMutex();
        });
        try {
            $this->execute();
        } finally {
            $this->removeMutex();
        }
    }
    /**
     * 过滤
     * @return bool
     */
    public function filtersPass()
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }
        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }
        return true;
    }
    /**TODO 生产任务标示
     * 任务标识
     */
    public function mutexName()
    {
        return 'task-' . sha1(static::class);
    }
    //TODO 删除任务
    protected function removeMutex()
    {
        return Cache::rm($this->mutexName());
    }
    //TODO 创建任务
    protected function createMutex()
    {
        $name = $this->mutexName();
        if (!Cache::has($name)) {
            Cache::set($name, true, $this->expiresAt);
            return true;
        }
        return false;
    }
    //TODO 获取任务标示
    protected function existsMutex()
    {
        return Cache::has($this->mutexName());
    }
    //TODO 过滤
    public function when(Closure $callback)
    {
        $this->filters[] = $callback;
        return $this;
    }
    //TODO 不执行
    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;
        return $this;
    }
    //TODO 任务是否可以重叠执行
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;
        return $this->skip(function () {
            return $this->existsMutex();
        });
    }
    //TODO 分布式部署
    public function onOneServer()
    {
        $this->onOneServer = true;
        return $this;
    }
}