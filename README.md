<img src="https://github.com/user-attachments/assets/689beb98-7175-4d30-8235-d89c629c0496" width="500" height="500">



# Scrippy - Laravel One-Off Script Manager

Scrippy is a Laravel package that helps you manage one-off scripts across multiple environments. Think of it as migrations, but for scripts that need to run exactly once (or a specific number of times) across your environments.

## Features

* 🚀 Environment-Aware: Scripts know which environments they've run in
* 🔄 Run Limiting: Set how many times a script should run and avoid race conditions on multi deployments
* 📊 Execution Tracking: Keep track of when and where scripts ran
* ✔️ Proof Checking: Sripcts can prove they ran properly
* 🤖 Auto-Cleanup: Automatically creates PRs to remove completed scripts
* 🔌 Easy Integration: Runs after migrations or on-demand

## Installation
```
composer require phadaphunk/scrippy
```

## Publish the config file

From there you can control when Scrippy runs, and various options like wether it should cleanup or not.


```
php artisan vendor:publish
```


## Migrate

Scrippy adds a single table to keep track of script executions. You can opt-out of it in the configurations if you do not need to keep traces.

```
php artisan migrate
```

## Create One-offs

Just create your scripts and let Scrippy do the work

```
php artisan make:scrippy
```
