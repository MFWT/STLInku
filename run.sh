#!/bin/bash

#！！！注意：请将里面的所有链接替换为你自己的！！！

#读入串流地址，和“FFAPI.php”中定义的文件名一样
address=`cat RTMPaddress.txt`
echo "$address"

#上游数据源服务器的地址
OriginURL="http://xxxxxxxx/"

#失败重试$retry次
retry=20

while [ $retry -ge 1 ]
do
    start=$(date +%s)
    sudo ffmpeg -re -rw_timeout 15000000 -i "$OriginURL" -c copy -bsf:a aac_adtstoasc -f flv "$address"
    sleep 1
    end=$(date +%s)
    let time=end-start

    if [ $time -lt 20 ];then
        #检测到运行时间小于20s，可能是数据源出错
        
        echo "数据源可能已经断开，正在尝试重新启动数据源"
        curl -X POST -d "ctrl=restart" "https://xxxxxxx/STLinkAPI.php"
        #重发启动指令，详见“STLinkAPI.php”
        echo "等待8秒"
	sleep 8
    fi

    retry=$(( $retry - 1 ))
    echo "---------------目前是第：$retry 次重试-----------------"
done
