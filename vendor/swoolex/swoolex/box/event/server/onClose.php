<?php
/**
 * +----------------------------------------------------------------------
 * 监听客户端退出事件
 * +----------------------------------------------------------------------
 * 官网：https://www.sw-x.cn
 * +----------------------------------------------------------------------
 * 作者：小黄牛 <1731223728@qq.com>
 * +----------------------------------------------------------------------
 * 开源协议：http://www.apache.org/licenses/LICENSE-2.0
 * +----------------------------------------------------------------------
*/

namespace box\event\server;

class onClose
{
    /**
	 * 启动实例
	*/
    public $server;

    /**
     * 统一回调入口
     * @author 小黄牛
     * @version v1.0.1 + 2020.05.26
     * @param Swoole\Server $server
     * @param int $fd 连接的文件描述符
     * @param int $reactorId 来自那个 reactor 线程，主动 close 关闭时为负数
    */
    public function run($server, $fd, $reactorId) {
        $this->server = $server;
        
    }
}

