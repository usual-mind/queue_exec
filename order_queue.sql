CREATE TABLE 'order_queue'(
'id' INT(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id号'，
'order_id' INT (11) NOT NULL,
'mobile' VARCHAR (20) NOT NULL COMMENT '用户的手机号'，
'created_at' datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '订单创建时间',
'updated_at' datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '处理完成时间',
'status' tinyint(2) NOT NULL COMMENT '当前状态：0  未处理；1  已处理；2  处理中',
PRIMARY KEY ('id')
)ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `Imooc`.`order_queue` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `order_id` INT(11) NOT NULL , `mobile` VARCHAR(20) NOT NULL COMMENT '用户的手机号' , `created_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '订单创建时间' , `updated_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '处理完成时间' , `status` TINYINT(2) NOT NULL COMMENT '当前状态：0 未处理；1 已处理；2 处理中' , PRIMARY KEY (`id`)) ENGINE = InnoDB;