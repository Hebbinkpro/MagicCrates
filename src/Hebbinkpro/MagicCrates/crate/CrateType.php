<?php

namespace Hebbinkpro\MagicCrates\crate;

use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\CrateCommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class CrateType
{
    /** @var CrateType[] */
    private static array $crateTypes = [];

    private string $id;
    private string $name;
    /** @var array<string, CrateReward> */
    private array $rewards;
    /** @var string[] */
    private array $commands;

    /**
     * @param string $id
     * @param string $name
     * @param array<string, CrateReward> $rewards
     * @param string[] $commands
     */
    public function __construct(string $id, string $name, array $rewards, array $commands)
    {
        $this->id = $id;
        $this->name = $name;
        $this->rewards = $rewards;
        $this->commands = $commands;

        self::$crateTypes[$id] = $this;


    }

    /**
     * @param string $id
     * @param array{name: string, rewards: array, commands: string[]} $data
     * @return CrateType|null
     */
    public static function decode(string $id, array $data, string &$errorMsg = ""): ?CrateType
    {
        $name = $data["name"] ?? "§6$id §cCrate§r";

        $rewards = [];
        foreach ($data["rewards"] ?? [] as $rewardData) {
            $reward = CrateReward::decode($rewardData, $errorMsg);

            // reward is not valid, that is not valid
            if ($reward === null) {
                $errorMsg = "Could not decode reward: $errorMsg";
                return null;
            }

            $rewards[$reward->getName()] = $reward;
        }

        $commands = $data["commands"] ?? [];

        // we cannot have a type without rewards
        if (count($rewards) == 0 && count($commands) == 0) {
            $errorMsg = "No rewards or commands given.";
            return null;
        }

        return new CrateType($id, $name, $rewards, $commands);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public static function getById(string $id): ?CrateType
    {
        return self::$crateTypes[$id] ?? null;
    }

    /**
     * @return string[]
     */
    public static function getAllTypeIds(): array
    {
        $ids = [];
        foreach (self::$crateTypes as $type) {
            $ids[] = $type->getId();
        }
        return $ids;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return CrateReward[]
     */
    public function getRewards(): array
    {
        return $this->rewards;
    }

    public function getRewardByName(string $name): ?CrateReward
    {
        return $this->rewards[$name] ?? null;
    }

    /**
     * Get a random reward from the rewards list
     * @return CrateReward
     */
    public function getRandomReward(): CrateReward
    {
        // construct a list of all available rewards
        $rewards = [];
        foreach ($this->rewards as $reward) {
            // each reward has its own 'amount', this is the amount of the same reward that's in the list
            for ($i = 0; $i < $reward->getAmount(); $i++) {
                $rewards[] = $reward;
            }
        }

        return $rewards[array_rand($rewards)];
    }

    public function executeCommands(Player $player, CrateReward $reward): void
    {

        $values = [
            "player" => $player->getName(),
            "crate_type" => $this->getId(),
            "crate" => $this->getName(),
            "reward" => $reward->getName()
        ];

        $commands = array_merge($this->commands, $reward->getCommands());

        foreach ($commands as $cmd) {
            CrateCommandSender::executePreparedCommand($cmd, $values);
        }
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCrateKey(): Item
    {
        $key = VanillaItems::PAPER();
        $key->setCustomName("[§6Crate Key§r] §e" . $this->id);
        $key->setLore([MagicCrates::PREFIX]);
        $key->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
        $key->getNamedTag()->setString(MagicCrates::KEY_NBT_TAG, $this->id);

        return $key;
    }

    /**
     * Get a list of all reward probabilities
     * @param bool $percentage change probabilities (0-1) to percentages (0-100)
     * @return array<string, float> a list of all reward probabilities
     */
    public function getRewardDistribution(bool $percentage = false): array
    {
        $dist = [];
        $totalAmount = $this->getTotalRewardsAmount();

        foreach ($this->rewards as $reward) {
            $dist[$reward->getName()] = $reward->getAmount() / $totalAmount;
            if ($percentage) $dist[$reward->getName()] *= 100;
        }

        return $dist;
    }

    /**
     * Get the total amount of rewards
     * @return int the summation of the amount of all rewards
     */
    public function getTotalRewardsAmount(): int
    {
        $amount = 0;
        foreach ($this->rewards as $reward) {
            $amount += $reward->getAmount();
        }

        return $amount;
    }
}