## A Simple Deployer Based on PHP-ssh

This is a simple Script that utilises composer packagers. Specially: Symfony components. The purpose is simple, it runs commands in other server via ssh. I usually ssh into my production server then run git pull and other necessary commands. I know jenkins can help me there. But few times I dont have the freedom to tinker client product server. 

### Install Dependency 


Install all dependency

* Current PHP Dependency is 5.5 or above
* Install Composer  `` $composer update ``
* Install php-ssh2 extension in php

### Create YAML 

Create a yaml file. Follow the sample.yml file. 

### Run Command 

``
$php bin/console deploy sample gs
``

This  will execute **gs** command from the **sample.yml** file. 

```yaml
  gs:
      label: 'GIT Status'
      pre:
        - default
      script:
        - git status
```

### Quick Q/A 

**Q. How to add a new server ?**  
`` 
Duplicate sample.yml and make necessary changes. 
`` 

**Q. I need to run multiple commands as a single command?**   

```yaml 
  gs:
      label: 'GIT Status'
      pre:
        - default
      script:
        - cd /home/public_html
        - git status
```
 

### TODO 

There are lots of room to improvement. 

* Interactive command
* Keep tty session so I can execute multiple commands without creating ssh connection everytime.

