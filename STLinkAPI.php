<?php
//���������
//����ʽ��POST 
//�����б�ctrl=��stop/start/restart��   link=���͹ܵ�ַ��

if(empty($_POST['ctrl']))
{
	die("API Control Error");
}
else
{
	if($_POST['ctrl']=="stop")
	{
        //�յ�ͣ��ָ��
		exec("sudo killall python3.6");
		die("OK");
	}
	if($_POST['ctrl']=="start")
	{
        //�յ�����ָ��Ѵ��������͹ܵ�ַ�洢�ã��Ա�����ʱʹ��
		$file=fopen("Ytbaddress.txt","w");
		fwrite($file,$_POST['link']);
        fclose($file);
		//����Streamlink
		$i=popen("sudo nohup ./STLink.sh ".$_POST['ctrl']." ".$_POST['link']." &","r");
		die("OK");
	}
    if($_POST['ctrl']=="restart")
    {
        //�յ�����ָ��Ѵ洢�õ��͹ܵ�ַ������Ȼ������Streamlink
        
        $add=file_get_contents("Ytbaddress.txt");
        $i=popen("sudo nohup ./STLink.sh "."start"." ".$add." &","r");
        die("OK");
    }

}
?>
