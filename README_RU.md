# Synology-GitSync
Синхронизация git проекта с Web папкой Synology

- [Index](README.md)
- [Readme на русском](README_RU.md)

## Инструкция:
- Положите в новую папку Web Services файлы проекта из этого репозитария ( она должна быть доступна извне, чтобы GitHub сервер смог доставить хук )
например подпапка \web\ GitReleased
- прописать в WebServices папку GitReleased, чтобы она стала доступной извне, например Example.com
- рядом с папкой создать ещё одну папку "configs", здесь будут лежать файлы конфигураций для различных синхронизаторов
- получить для выполняемого скрипта полный путь, и получить от него md5
- в папке "configs" создать .php файл без расширения с этим именем. Такая заморочка сделана специально, чтобы можно было без изменений файла скрипта из разных папок синхронизировать проекты разными файлами конфигураций. Впринципе достаточно просто прописать в Include_once фиксированное имя файла
- в этом файле положить примерно такое содержимое:
```
<?PHP
$SynoProject = [];
$SynoProject['TargetFolder'] = 'SyncedProject';
$SynoProject['TargetBase'] = '/var/services/web/'; // путь к проекту
$SynoProject['Backup'] = '/var/services/homes/web/'; // куда складывать бекапы перед синхронизацией
$SynoProject['On']['repo_full_name'] = 'PhantomCity/Synology-GitSync'; //полное имя проекта в GitHub;
$SynoProject['On']['!Master-KEY'] = ключ; // по нему не могу настроить верификацию, что опасно;
$SynoProject['On']['branch'] = 'refs/heads/master'; // имя ветки, при обновлении которой забрать исходники
$SynoGitSync_Profile['SynoProject/AnyName'] = $SynoProject; // сохранение профиля для синхронизации
?>
```

Если настроить так, чтобы index.php был доступен по Example.com/GitReleased/ и в WebHook указать этот адрес,
то каждый пуш будет дёргать ссылку, в неё будет передаваться параметры проекта и проверяться 'repo_full_name' и 'branch',
если будет соответствие, то скрипт скачает Zip папку проекта ( пока только master ветку, а не указанную ), распакует её и заменит существующую,
укзанную в 'TargetBase' + 'TargetFolder'.

## TODO
- [ ] [Не работает верификация X_HUB_SIGNATURE](https://github.com/PhantomCity/Synology-GitSync/issues/2) так ни один пример и не заработал
- [ ] [Сломалось бекапирование](https://github.com/PhantomCity/Synology-GitSync/issues/1) пока добавлял пораметр ['Backup'], сломалось :( 

За основу взят [@dintel/php-github-webhook](https://github.com/dintel/php-github-webhook)
