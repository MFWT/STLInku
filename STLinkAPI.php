<?php
//国外机器用
//请求方式：POST 
//参数列表：ctrl=【stop/start/restart】   link=【油管地址】

if(empty($_POST['ctrl']))
{
	die("API Control Error");
}
else
{
	if($_POST['ctrl']=="stop")
	{
        //收到停机指令
		exec("sudo killall python3.6");
		die("OK");
	}
	if($_POST['ctrl']=="start")
	{
        //收到开机指令，把传过来的油管地址存储好，以备重启时使用
		$file=fopen("Ytbaddress.txt","w");
		fwrite($file,$_POST['link']);
        fclose($file);
		//启动Streamlink
		$i=popen("sudo nohup ./STLink.sh ".$_POST['ctrl']." ".$_POST['link']." &","r");
		die("OK");
	}
    if($_POST['ctrl']=="restart")
    {
        //收到重启指令，把存储好的油管地址读出，然后塞给Streamlink
        
        $add=file_get_contents("Ytbaddress.txt");
        $i=popen("sudo nohup ./STLink.sh "."start"." ".$add." &","r");
        die("OK");
    }

}
?>
