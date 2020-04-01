# STLInku

#### 油管直播转发系统搭建教程
---
### 前言

在贝组里面呆久了，总觉得要为组里面做些什么好，于是想着想着就有了这个“**我将在此示范如何把你的设计师朋友气到脑中风**”的项目

项目一开始是为了组里面使用的，**因此本项目的通用型可能会稍差**，但我会努力让他变得好用

**！！有一点需要注意的：当时写这个系统只是为了能用还不坏，因此很多优化都没有进行，请知悉！！**

开发协助：[小白(mmdllsg)](https://github.com/mmdllsg)



本项目使用到了以下软件，在此表示感谢！

* [nginx](https://nginx.org/)
* [ffmpeg](https://ffmpeg.org/)
* [酷Q机器人](https://cqp.cc/)
* [Streamlink](https://streamlink.github.io/)
* [易语言](https://www.dywt.com.cn/)

---

### 实现

STLInku的原理并不难，见如下流程 “字”：

##### 1.（油管直播）--->（Streamlink获取链接并生成一个流服务器）--->(ffmpeg主动拉流并串流)

##### 2.（用户发送开关请求）--->（酷Q接收，并交由插件处理）--->（插件访问开关API）

---

### 部署

需要准备好的有：

* 国外VPS（**必须**，作为数据源，以CentOS 7为例）

* 国内VPS（转发用）（**必须**，很多情况下b站要求海内IP，以Debian 10为例）

* 国内VPS（酷q控制用）（可选，**如果要用到酷q控制的话**）

* 域名，SSL证书（可选，**建议加上**，这样与国外服务器通信就可以采用更安全的HTTPS方式）


---

# 国外篇

#### 第零步　选购VPS

选购设备的标准，和选购魔法上网工具的标准是类似的，**切记：RAM不要太小（大于等于512MiB为宜）**

#### 第一步，在国外vps上，安装PHP，nginx并配置SSL。

这个步骤可以参考其他文章，这里就不再阐述了。**如果使用HTTPS，请注意编译安装nginx时带上SSL选项**

SSL证书申请可以使用[acme.sh](http://acme.sh/)

安装完成后，修改nginx.conf，调整如下内容**并重启nginx**，如下图所示

```nginx
server {
	listen 443 ssl ; #如果不使用SSL，请改成 listen 80; 
	server_name xxxx.xxx; #根据实际情况修改
    
    
    #使用SSL的话，在此处配置SSL，具体可参考其他文章
    
    location / {
           root /usr/local/nginx/html;#这里改为网站根目录
           index index.php;
        }
    
        location ~ \.php$ {   #php配置
         fastcgi_pass   127.0.0.1:9000;  #php-fpm在9000端口监听
         fastcgi_index  index.php;
         fastcgi_param  SCRIPT_FILENAME  /usr/local/nginx/html$fastcgi_script_n$
            							#这里改为：网站根目录$fastcgi_script_n$
         include        fastcgi_params;
        }
    
}
```

**特别注意：Streamlink不推荐使用nginx进行反代，原因下文讲述**

#### 第二步，安装Streamlink。

由于CentOS自带的Python是2.x版本，因此必须手动安装Python3.x版本，这里使用编译安装的方式

```shell
#举例：Python3.6
#安装依赖
yum install make gcc gcc-c++
yum -y install zlib*
yum install readline-devel
#下载py3.6
wget https://www.python.org/ftp/python/3.6.3/Python-3.6.3.tgz
tar zxvf Python-3.6.3.tgz
cd Python-3.6.3
./configure
make
sudo make insatll
```

新旧版本的Python可以同时存在，使用时访问python3.6和pip3即可

接下来正式安装Streamlink

```shell
#注意，范例是使用上面编译好的Python！
pip3 install streamlink
```

这里有个小小的坑：当时我安装完毕后，使用其他用户登录，输入streamlink提示找不到命令。因此建议在下一步使用PHP调用执行时，使用绝对路径：

```php
<?php
    //范例
	$i=popen("sudo nohup /usr/local/bin/python3.6 /usr/local/bin/streamlink &","r");
	//使用popen，可以让PHP非阻塞运行程序
	die("OK");
?>
```



确认安装完毕后，可以测试一下：

注意：这里的各项参数请根据自己的情况**酌情**修改！

```shell
streamlink <油管链接> \
best \ 								#输出画质最好
--player-external-http \ 			#以HTTP流的形式对外输出，而不是调用播放器
--player-external-http-port 8080 \  #输出端口8080，与上面nginx的反代地址一样
--retry-open 30 \ 					#链接失败时，重复请求30次
--hls-segment-timeout 600 \ 		#HLS每个切片的最大超时（单位：秒）
--hls-timeout 900 \				 	#HLS最大超时（单位：秒）
--http-stream-timeout 900 \ 		#HTTP流最大超时（单位：秒）
--ringbuffer-size 4M \ 				#缓存大小，默认是16m，在低配置小内存机器下建议调小
```

执行后稍等一会儿，当看到控制台输出类似于：

```shell
[cli][info]   http://127.0.0.1:8080/
[cli][info]   http://<公网IP>:8080/
```

时，就说明Streamlink启动成功，可以在VLC中打开：http://<公网IP>:8080/   来观看直播

![测试](https://mfwt.github.io/VLCPlaying.png)

# 国内篇

#### 第零步：设备购买

完成上面的步骤后，转播man就可以有一个稳定的数据源来使用了。但我们的目标是实现自动转播。

由于众所周知的原因，除特殊情况外，B站不允许海外IP开直播。同时，流量都走在国内VPS的话，可以减少这样那样的麻烦。

**还是众所周知的原因，国内VPS（例如套路云和良心云的）的带宽通常都非常贵，因此在这里我们有个奇技淫巧：**

### NAT VPS！

NAT（Network Address Translation，网络地址转换），通俗来说就是让多个设备共用一个IP地址。举例来说，你家的**路由器**，就是NAT的一个应用。

由于省下了IP地址的钱，因此这类机器通常相比于同类产品而言，价格低，带宽大（**共享带宽**），对于我们的应用来说，足够了。

---

#### 第一步：国内转发对接

国内服务器的系统是Debian 10，在服务器上安装好ffmpeg和nginx和PHP

```shell
sudo apt-get upgreade
sudo apt-get install ffmpeg
sudo apt-get install nginx
sudo apt-get install php php-fpm php-cli
```

启动转发的时候，请使用以下命令：

```powershell
ffmpeg \
-rw_timeout 30000000 \                            #超时值：30s
-i http(s)://<你的国外vps的ip或者域名>:8080/ \       #流输入
-c copy \                                         #直接复制流
-f flv \                                          #重新封装为flv
-bsf:a aac_adtstoasc\                             #对于flv格式中的AAC，需要用到这个filter
<b站串流地址+密钥>                                  #流输出
```

在b站点开播键，并执行以上命令后，就可以在直播间看到输出了。

![](https://mfwt.github.io/输出.jpg)

**上文提到过不建议用nginx反代，是因为根据测试来看，使用nginx反代有一定几率会导致断流，特别是在线路不好，网络波动大的情况下。个中缘由怀疑是因为Streamlink的特性导致的**

---

### 第四步：远程控制

现在系统已经运行起来了，当然，你肯定不想每一次都SSH登录上去来控制这个软件。本项目附带了用PHP和Shell写的API，可以方便控制整个系统



>* FFAPI.php：放置在**国内**VPS，用于控制ffmpeg的启停，以及更新RTMP地址
>* run.sh：放置在**国内**VPS，用于配合FFAPI.php启停ffmpeg

  

>* STLinkAPI.php：放置在**国外**VPS，用于启停Streamlink，以及更新油管地址
>* STLink.sh：放置在**国外**VPS，用于配合STLinkAPI.php启停Streamlink

利用API可以方便地控制，这里以酷Q控制为例：

![](https://mfwt.github.io/酷q控制.PNG)



从半个月前开始，截止到目前，这个项目已经转播不下五次

可见稳定性还是可以的。

### 写在最后

这个项目慢慢折腾起来，倒也能用，希望能给大家带来小小的一点帮助

#### （全文完）
