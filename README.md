# 欢迎使用 AliExpress-Monitor 监控系统

------

这是一个在实际应用场景下的软件。
通过Curl 抓取网页源代码 来监控 目标字段的变化
#支持监控属性

1.销量
2.名称
3.url
4.加购人数
5.待添加

##安装指南

1.创建数据库导入数据库文件aliexpress.sql

2.修改数据库配置文件AliExpress-Monitor\application\database.php

3.配置Web服务器运行目录AliExpress-Monitor\public

4.cli模式运行 监控程序

```
cd XXXX/AliExpress-Monitor
php think AliExpress ProductsList 获取产品
php think AliExpress ProductsInfo 获取详情
```
5.访问即可查看结果