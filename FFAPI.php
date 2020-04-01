<?php

//请求方式：POST 
//参数列表：ctrl=【stop/start/updateRTMP】   address=【B站RTMP串流地址+串流码】
if(empty($_POST['ctrl']))
{
	//没有检测到控制指令
	die("API Control Error");
}
else
{
	if($_POST['ctrl']=="stop")
	{
		//run.sh是一个很特殊的设计，里面运行ffmpeg的方式是：
		//把ffmpeg放在一个循环里面，这样无论什么原因导致ffmpeg退出
		//（包括人工中断）程序都会自动重启ffmpeg（自动重试指定次数）
		//这样写的好处就是：如果遇到网络波动等情况，程序会自动恢复
		//并且，简单粗暴好用
		//那么这种写法，普通的直接killall ffmpeg就不好使了
		//换句话说，我们要用点奇技淫巧来按需终止运行：

		$output = shell_exec('sudo killall run.sh');
		//第一步，砍掉守护进程，这样程序就不会自动重启
		$output = shell_exec('sudo killall ffmpeg');
		//第二步，砍掉ffmpeg，程序停止运行

		die("OK");
	}
	if($_POST['ctrl']=="start")
	{
		$i=popen("sudo nohup /usr/local/nginx/html/run.sh &","r");
		//也正因为守护进程的原因，非阻塞运行ffmpeg就交给了run.sh
		die("OK");
	}
    if($_POST['ctrl']=="updateRTMP")
    {
        //接收到该命令，程序自动把参数‘address’的内容存入同一目录下的RTMPaddress.txt
        $file=fopen("RTMPaddress.txt","w");
        fwrite($file,str_ireplace("&amp;","&",$_POST['address']));//这里转码的意义是：把数据传输时的“&amp;”转换为“&”
        fclose($file);
        die("OK");
    }
}
?>
