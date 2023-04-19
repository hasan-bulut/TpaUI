<?php

namespace TpaPlugin;

use pocketmine\Server;
use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;
use pocketmine\Command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

use TpaPlugin\libs\jojoe77777\FormAPI\{CustomForm, ModalForm, SimpleForm};

class Main extends PluginBase
{
    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "tpaistekleri.yml", Config::YAML);
        foreach ($this->config->getAll(true) as $c) {
            $this->config->remove($c);
            $this->config->save();
        }
        $this->getLogger()->info("TPA Aktif");
    }

    public function onCommand(CommandSender $gönderen, Command $komut, String $label, array $args): bool
    {
        if ($komut == "tpa") {
            if ($gönderen instanceof Player) {

                $this->TpaAna($gönderen); //Tpa Ana Formunu Açar.

            } else {
                $gönderen->sendMessage("§cBu Komut Konsolda Kullanılamaz!");
            }
        }
        return true;
    }

    public function TpaAna(Player $oyuncu)
    {
        $form = new SimpleForm(function (Player $oyuncu, int $data = null) {
            if ($data === null) {
                return true;
            }

            switch ($data) {
                case 0:
                    $this->Tpaform($oyuncu); //Tpa Gönderme FOrmunu Açar.
                    break;
                case 1:
                    $this->Tpaistekler($oyuncu); //Tpa İsteklerinin Bulundupu Formu Açar.
            }
        });
        $form->setTitle("§l§6Tpa Menü");
        $form->setContent("§l§6>>§r§e Hoşgeldin " . $oyuncu->getName() . "! §r§eAlttaki Butonlardan Seçim Yapabilirsin");
        $form->addButton("§l§8Yeni Tpa İsteği\n§l§6>>§r§eAçmak İçin Tıklayın§l§6<<");
        $form->addButton("§l§8Tpa İstekleri\n§l§6>>§r§eAçmak İçin Tıklayın§l§6<<");
        $form->sendToPlayer($oyuncu);
    }

    public function Tpaform(Player $oyuncu)
    {
        $form = new CustomForm(function (Player $oyuncu, array $data = null) {
            $list = [];
            foreach ($this->getServer()->getOnlinePlayers() as $p) //Online Kişileri Çeker(Player Türünde).
            {
                if (!$p == $oyuncu) {
                    $list[] = $p;
                }
            }

            if ($data === null) {
                return true;
            }

            if (!count($list) == 0) {
                $index = $data[1];
                $hedef = $list[$index]; //İndex
                $tpaistek = $this->config->get($hedef->getName());
                $tpaAtanlar = [];
                if (!$tpaistek == null) {
                    $tpaAtanlar = explode(" ", $tpaistek);
                    array_unique($tpaAtanlar);
                }
                array_push($tpaAtanlar, $oyuncu->getName());
                $this->config->set($hedef->getName(), (string)implode(" ", $tpaAtanlar));
                $this->config->save();
                if ($hedef->hasPermission("tpa.ekran")) //Kişiye Tpa Atıldığı An Onay Formu Çıkmasının İzini.(Ayarlar İçin)
                {
                    $this->onayForm($list[$index], $oyuncu);
                } else {
                    $hedef->sendMessage("§6" . $oyuncu->getName() . " §aSize Işınlanma İstediği Yolladı. Kabul Etmek İçin TPA Menüsünden İsteklerinize bakabilirsiniz.");
                }
            }
        });
        $list = [];
        foreach ($this->getServer()->getOnlinePlayers() as $p) //Online Kişileri Çeker(Player Türünde).
        {
            if (!$p == $oyuncu) {
                $list[] = $p;
            }
        }
        $form->setTitle("Tpa Formu");
        $listcik = [];
        if (count($list) != 0) {
            foreach ($list as $p) {
                $listcik[] = $p->getName(); //Dropdownd kulllanabilmek için isimleri player türünden normal isim türüne çevirdim.
            }

            $form->addLabel("Tpa Atmak İstediğin Kişiyi Seç");
            $form->addDropdown("İsim:", $listcik);
        } else {
            $form->addLabel("§cSunucuda Işınlanabileceğin Kimse Yok");
        }

        $form->sendToPlayer($oyuncu);
    }

    public function Tpaistekler(Player $oyuncu)
    {
        $form = new SimpleForm(function (Player $oyuncu, int $data = null) {
            $tpaistek = $this->config->get($oyuncu->getName());
            if (!$tpaistek == null) {
                $tpaAtanlar = [];
                $tpaAtanlar = explode(" ", $tpaistek);
            } else {
                $tpaAtanlar[0] = null;
            }

            if ($data === null) {
                return true;
            }

            if (!$tpaAtanlar[0] == null) {
                $list = [];
                foreach ($this->getServer()->getOnlinePlayers() as $p) {
                    $list[$p->getName()] = $p;
                }

                for ($sayi = 0; $sayi < count($tpaAtanlar); $sayi++) {
                    $indexler[] = $sayi; //Butonların İndexleri
                }

                foreach ($indexler as $i) {
                    switch ($data) {
                        case $i:
                            if (($key = array_search($i, $indexler)) !== false) {
                                unset($indexler[$key]); //Tıklanan Butonun İndexini Kaldırmak
                            }
                            $this->onayForm($list[$tpaAtanlar[$i]], $oyuncu);
                    }
                }
            }
        });
        $tpaistek = $this->config->get($oyuncu->getName());
        if (!$tpaistek == null) {
            $tpaAtanlar = [];
            $tpaAtanlar = explode(" ", $tpaistek);
        } else {
            $tpaAtanlar[0] = null;
        }
        $form->setTitle("§l§6Tpa İsteklerim");
        $form->setContent("§l§6>>§r§eAşağıda Önceki Tpa İsteklerin Görünür!");
        if ($tpaAtanlar !== null) {
            if (!$tpaAtanlar[0] == null) //Oyunucnun herhangi bi isteiği olup olmadını kontrol eder.
            {
                if ($tpaAtanlar !== null) {
                    foreach ($tpaAtanlar as $kisi) {
                        $form->addButton("§6" . $kisi . "\n§l§6>>§r§eKabul Etmek İçin Tıklayın§l§6<<");
                    }
                }
            } else {
                $form->setContent("§cHiç Işınlanma İstediğin Yok!");
                $form->addButton("§cÇIKIŞ");
            }
        }
        $form->sendToPlayer($oyuncu);
    }

    public $tpaGönderen;

    public function onayForm(Player $gönderen, Player $gönderilen) //Onay Forma Çektiğim Veriler
    {
        global $tpaGönderen;
        $tpaGönderen = $gönderen;
        $form = new ModalForm(function (Player $gönderilen, bool $data) {
            global $tpaGönderen;

            $tpaistek = $this->config->get($gönderilen->getName());
            $tpaAtanlar = [];
            $tpaAtanlar = explode(" ", $tpaistek);

            if ($data === null) {
                return true;
            }

            if ($data == true) {
                $list = [];
                foreach ($this->getServer()->getOnlinePlayers() as $p) {
                    $list[] = $p;
                }
                if (in_array($gönderilen, $list)) {
                    $tpaGönderen->teleport($gönderilen->getPosition());
                } else {
                    $tpaGönderen->sendMessage("§6" . $gönderilen . " §cOyundan Çıkmış!");
                }
                if (($key = array_search($tpaGönderen->getName(), $tpaAtanlar)) !== false) {
                    unset($tpaAtanlar[$key]);
                    $this->config->set($gönderilen->getName(), (string)implode(" ", $tpaAtanlar));
                    $this->config->save();
                }
                $tpaGönderen->sendMessage("§l§6>>§a" . $gönderilen->getName() . "§a Adlı Kişiye Başarılı Bir Şekilde Işınlandınız.");
                $gönderilen->sendMessage("§l§6>>§a" . $tpaGönderen->getName() . "§a Adlı Kişiye Başarılı Bir Şekilde Size Işınlandı.");
            } else {
                if (($key = array_search($tpaGönderen->getName(), $tpaAtanlar)) !== false) {
                    unset($tpaAtanlar[$key]);
                    $this->config->set($gönderilen->getName(), (string)implode(" ", $tpaAtanlar));
                    $this->config->save();
                }
                $this->Tpaistekler($gönderilen);
                $tpaGönderen->sendMessage("§l§6>>§c" . $gönderilen->getName() . "§c Adlı Kişi Işınlanma İsteğinizi Reddetti.");
            }
        });
        $form->setContent("§l§6>>§b" . $tpaGönderen->getName() . " §bSize Işınlanmak İstiyor.Kabul Edicekmisin?");
        $form->setButton1("§l§2Evet");
        $form->setButton2("§l§4Hayır");
        $form->sendToPlayer($gönderilen);
    }
}
