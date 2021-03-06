<?php

/**
 * memcached 类
 *
 * @package lib
 * @subpackage plugins.cache
 * 
 */
class Memcached
{
	/**
	 * @var Memcache $memcache Memcached 缓存连接对象
	 * @access public
	 */
	public $memcache = NULL;
	
	/**
	 * @var string $prefix 变量前缀
	 */
	public $prefix = '';
	
	/**
	 * 数据查询的统计
	 *
	 * @var Array
	 */
	public static $querys = array();
	
	/**
	 * 数据缓存沲
	 *
	 * @var Array
	 */
	public static $data = array();

	/**
	 * 构造函数
	 * @param string $host Memcached 服务器的主机名或IP地址或者为服务器组相关信息
	 * @param int $port 端口号
	 * @param int $timeout 超时时间
	 */
	public function __construct($host = 'localhost', $port = 11211, $timeout = 60)
	{
		$this->memcache = new Memcache();
		
		$host = is_array($host) ? $host : array(array('host' => $host, 'port' => $port));
		
		//如果是服务器分组则添加所有的服务器分组
		foreach ($host as $m)
		{
			$this->memcache->addServer($m['host'], $m['port']);
		}
	}

	/**
	 * 析构函数
	 */
	public function __destruct()
	{
		$this->memcache->close();
	}

	/**
	 * 在cache中设置键为$key的项的值，如果该项不存在，则新建一个项
	 * @param string $key 键值
	 * @param mix $var 值
	 * @param int $expire 到期秒数
	 * @param int $flag 标志位
	 * @return bool 如果成功则返回 TRUE，失败则返回 FALSE。
	 * @access public
	 */
	public function set($key, $var, $expire = 0, $flag = 0)
	{
		$key = $this->prefix . $key;
		
		if (DEBUG)
			self::$querys[] = "set " . $key . ' ' . $var;
		
		self::$data[$key] = $var;
		
		return $this->memcache->set($key, $var, $flag, $expire);
	}

	/**
	 * 在cache中获取键为$key的项的值
	 * @param string $key 键值
	 * @return string 如果该项不存在，则返回false
	 * @access public
	 */
	public function get($key)
	{
		$key = (empty($this->prefix)) ? $key : $this->prefix . $key;
		
		if (DEBUG)
			self::$querys[] = "get " . (is_array($key) ? implode(',', $key) : $key);
		
		if (is_array($key))
		{
			$v_data = $k_data = array();
			foreach ($key as $v)
			{
				if (array_key_exists($v, self::$data))
					$v_data[$v] = self::$data[$v];
				else
					$k_data[] = $v;
			}
			
			if (count($k_data) > 0)
			{
				$k_data = $this->memcache->get($k_data);
				if (is_array($k_data) && count($k_data) > 0)
				{
					$v_data = array_merge($v_data, $k_data); //合并到返回数组
					self::$data = array_merge(self::$data, $k_data); //合并到缓存数组					
				}
			}
			
			return $v_data;
		}
		else
		{
			if (! array_key_exists($key, self::$data))
				self::$data[$key] = $this->memcache->get($key);
			
			return self::$data[$key];
		}
	}

	/**
	 * 在MC中获取为$key的自增ID
	 *
	 * @param string $key	 自增$key键值
	 * @param integer $count 自增量,默认为1
	 * @return 				 成功返回自增后的数值,失败返回false
	 */
	public function increment($key, $count = 1)
	{
		return $this->memcache->increment($key, $count);
	}

	/**
	 * 清空cache中所有项
	 * @return 如果成功则返回 TRUE，失败则返回 FALSE。
	 * @access public
	 */
	public function flush()
	{
		return $this->memcache->flush();
	}

	/**
	 * 删除在cache中键为$key的项的值
	 * @param string $key 键值
	 * @return 如果成功则返回 TRUE，失败则返回 FALSE。
	 * @access public
	 */
	public function delete($key)
	{
		if (array_key_exists($key, self::$data))
			unset(self::$data[$key]);
		
		return $this->memcache->delete($this->prefix . $key);
	}
}
