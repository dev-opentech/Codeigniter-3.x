create table usuario(
id_usuario int not null primary key auto_increment,
nombre_usuario varchar(200),
contrasenia varchar(200),
rela_persona int,
rela_tipo_usuario int,
url_update varchar(100),
time_update_pass datetime,
);

create table persona(
id_persona int not null primary key auto_increment,
nombre_persona varchar(100),
apellido_persona varchar(100),
email_persona varchar(100),
);
