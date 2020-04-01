#!/bin/bash

#命令行格式：./STLink.sh <控制命令（start/stop）> <油管地址>

if [ "$1"x = "start"x ]; then
  if [ ! -n "$2" ] ;then
      echo -e "\033[1;33m 没有检测到传参流地址！ \033[0m"
      exit
  else
      sudo killall python3.6
      sudo killall streamlink
      echo "检测到传参流地址： $2"
      echo "Streamlink正在后台启动中，可能需要5-10s才能有输出。"
      echo "对外输出的端口号是8080，当然已经由nginx转为443端口。"
     # echo "">nohup.out
      sudo nohup /usr/local/bin/python3.6 /usr/local/bin/streamlink "$2" best --player-continuous-http --player-external-http --player-external-http-port 8080 --retry-open 50 --hls-segment-timeout 600 --hls-timeout 900 --http-stream-timeout 900 --ringbuffer-size 4M &
  fi
fi

if [ "$1"x = "stop"x ]; then
  sudo killall streamlink
  sudo killall python3.6
   echo -e "\033[1;33m 进程结束完毕 \033[0m"
fi