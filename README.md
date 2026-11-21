# MultiVersion-Altay

A PocketMine-MP plugin that lets players on different Minecraft Bedrock versions join the same server.

It also includes a ready-to-use copy of [InvMenu](https://github.com/Muqsit/InvMenu) in the same repository, so you don't need to install it separately.

## Requirements

This plugin runs on [Altay](https://github.com/altayofficial). It will not work on stock PocketMine-MP.

## Supported versions

| Version  | Protocol | Notes                      |
|----------|----------|----------------------------|
| Latest   | 2225     | Native server version      |
| 1.21.114 | 844      | Normal Xbox authentication |
| 0.15.10  | 84       | No Xbox authentication     |

## Installation

1. Build the plugin into a `.phar` or drop the folder into `plugins/`.
2. Start the server so `plugin_data/MultiVersion/config.yml` is generated.
3. Open your server's UDP port (usually `19132`).

## Configuration

| Option               | Description                                    |
|----------------------|------------------------------------------------|
| `enable-raknet`      | Enables the RakNet transport                   |
| `enable-nethernet`   | Enables the NetherNet transport                |
| `disabled-protocols` | Protocols you want to disable                  |
| `allowed-protocols`  | If not empty, only these protocols are allowed |
| `debug`              | Prints extra information to the console        |

## Commands

| Command              | Description                    |
|----------------------|--------------------------------|
| `/protocol <player>` | Shows the protocol of a player |

## Bundled InvMenu

InvMenu is included in this repository at `src/libs/muqsit/invmenu`, under the namespace `libs\muqsit\invmenu`. MultiVersion registers it on its own at startup, so it works on every supported version without installing anything else.

To use it from another plugin:

1. Add `depend: [MultiVersion]` to your plugin's `plugin.yml`.
2. Do not bundle the original InvMenu or any other copy in your plugin. Use only this one.
3. Do not call `InvMenuHandler::register()`. MultiVersion already does it, and registering twice throws an exception. If you really need to, guard it with `InvMenuHandler::isRegistered()`.

```php
use libs\muqsit\invmenu\InvMenu;
use libs\muqsit\invmenu\transaction\InvMenuTransaction;
use libs\muqsit\invmenu\transaction\InvMenuTransactionResult;

$menu = InvMenu::create(InvMenu::TYPE_CHEST);
$menu->setName("My menu");
$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
    // $transaction->getPlayer(), $transaction->getItemClicked(), etc.
    return $transaction->discard(); // cancels the item movement
});
$menu->send($player);
```

Available types: `TYPE_CHEST`, `TYPE_DOUBLE_CHEST` and `TYPE_HOPPER`.

## API

```php
use lol\MultiVersionAPI;

MultiVersionAPI::getSupportedProtocols();
MultiVersionAPI::getPlayerProtocol($player);
MultiVersionAPI::getPlayerVersionName($player);
MultiVersionAPI::isLatest($player);
MultiVersionAPI::isLegacy($player);
MultiVersionAPI::isProtocol($player, MultiVersionAPI::PROTOCOL_1_21_114);
```

## Important

Players on 0.15.10 join without Xbox authentication, so anyone can use another person's name. Protect anything tied to player names (ranks, permissions, economy) with another method, such as a login password plugin.

## Credits

- [JoggingSplash/MultiVersion](https://github.com/JoggingSplash/MultiVersion): the base project this plugin comes from.
- [Flonja](https://github.com/Flonja): for their work on multi-version support for Bedrock.
- [Muqsit/InvMenu](https://github.com/Muqsit/InvMenu): the original InvMenu, included in this repository.
- [Altay](https://github.com/altayofficial): the server software this plugin runs on.
- [pmmp/PocketMine-MP](https://github.com/pmmp/PocketMine-MP): the software Altay is built on.
- [Claude](https://www.anthropic.com/claude) (Anthropic): help in developing this project.
