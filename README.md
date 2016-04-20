# b374k webshell 4.1

b374k webshell是一个非常实用的web管理工具（后门），所有的管理操作都通过HTTP协议进行。

## 特性 : 
 * 文件管理 (查看、编辑、重命名、删除、上传、下载、打包、解压等)
 * 搜索文件夹、文件、文件内容 (支持正则)
 * 命令执行
 * 脚本执行 (php, perl, python, ruby, java, node.js, c)
 * 支持正向/反向 shell
 * 内附简单的发包功能（内网探测神器）
 * 多个数据库支持 (mysql, mssql, oracle, sqlite, postgresql 等 ODBC、 PDO 支持的数据库)
 * SQL可视化浏览器
 * 进程管理器（可列出/杀死）
 * 支持附件的邮件发送功能 (你可以将服务器上的文件作为附件发送)
 * 字符串转换功能
 * 所有的功能都继承于一个文件，无需安装
 * 通信过程简单加密，虽不能保证通信内容不被窃听，但可以绕过一些自动化WAF的检测
 * 支持 PHP > 4.3.3 || PHP 5
 * 支持手工指定编码，防止多语言下的乱码问题

## 需求 :
 * PHP version > 4.3.3 || PHP 5
 * 为了支持zepto.js v1.1.2，你需要使用现代浏览器访问b374k。你可以在官网查看 zepto.js 支持的浏览器： http://zeptojs.com/
 * 其他需求取决于你要用这个shell做什么事情
 
## 安装 :

b374k shell是一个可装卸的模块化webshell，你需要进行一些简单的配置，才可以生成webshell文件。

下载b374k项目，你可以在命令行下运行index.php，查看其帮助文档：

```
git clone https://github.com/phith0n/b374k.git
cd b374k
php -f index.php -- --help
```

命令行参数说明：

```
$ php -f index.php
b374k shell packer 0.4

options :
        -o filename                             指定生成文件名
        -p password                             指定webshell密码
        -t theme                                指定皮肤
        -m modules                              指定模块，多个模块间用英文逗号分隔
        -s                                      是否清楚空白字符和注释
        -b                                      是否使用base64编码
        -z [no|gzdeflate|gzencode|gzcompress|rc4]   使用哪个压缩方式（需要开启 -b）
        -c [0-9]                                压缩等级
        -l                                      列出所有可用的模块
        -k                                      列出所有可用的皮肤
        -u code                                 指定目标系统编码，如gb2312/utf-8等，默认utf-8
```

例子：

```
php -f index.php -- -o myShell.php -p myPassword -s -b -z gzcompress -c 9
```

或者，你可以直接从浏览器访问index.php，使用图形化界面生成你的webshell：

![](http://7xkhqo.com1.z0.glb.clouddn.com/2016-02-16-14556165692049.jpg)

请不要在生成环境使用这个webshell，使用完成后删除所有文件。因为该webshell是一个后门，所以并没有安全防范措施。（特别是index.php，不要让他人访问到了，切记）

### 使用RC4加密webshell

如果你使用了RC4加密方法（-z rc4），那么在生成webshell的时候，请记下RC4 Cipher Key：

![](http://7xkhqo.com1.z0.glb.clouddn.com/2016-02-16-14556170735375.jpg)

在访问该shell的时候需要带上RC4-KEY头：

![](http://7xkhqo.com1.z0.glb.clouddn.com/2016-02-16-14556171853058.jpg)

否则将无法正常访问该shell。

## 开发文档 :
无

## 升级日志 :

 - 20160216 / Ajax通信使用RC4加密通道，避免受到WAF影响 / @phith0n
 - 20160216 / 压缩方法中加入RC4，可防止他人分析webshell / @phith0n
 - 20160420 / 增加编码选项，以后中文再也不会乱码啦~ / @phith0n

## 老版本 :

 - https://github.com/b374k/b374k
 - https://code.google.com/p/b374k-shell/


