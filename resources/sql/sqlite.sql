-- #!sqlite
-- #{ table
-- #{   crates
CREATE TABLE IF NOT EXISTS Crates
(
    x     INTEGER,
    y     INTEGER,
    z     INTEGER,
    world VARCHAR(255) NOT NULL,
    type  VARCHAR(255) NOT NULL,
    PRIMARY KEY (x, y, z, world)
);
-- #}
-- #{   rewards
CREATE TABLE IF NOT EXISTS Rewards
(
    type   VARCHAR(255) NOT NULL,
    player VARCHAR(255) NOT NULL,
    reward VARCHAR(255) NOT NULL,
    amount INTEGER DEFAULT 0,
    PRIMARY KEY (type, player, reward)
);
-- #}
-- #}

-- #{ data
-- #{   crates
-- #{       add
-- #            :x int
-- #            :y int
-- #            :z int
-- #            :world string
-- #            :type string
INSERT INTO Crates(x, y, z, world, type)
VALUES (:x, :y, :z, :world, :type);
-- #}
-- #{       remove
-- #            :x int
-- #            :y int
-- #            :z int
-- #            :world string
DELETE
FROM Crates
WHERE x = :x
  AND y = :y
  AND z = :z
  AND world = :world;
-- #}
-- #{       getAll
SELECT *
FROM Crates;
-- #}
-- #}

-- #{   rewards
-- #{       setPlayerRewards
-- #            :type string
-- #            :player string
-- #            :reward string
-- #            :amount int
INSERT OR
REPLACE INTO Rewards(type, player, reward, amount)
VALUES (:type, :player, :reward, :amount);
-- #}
-- #{       getPlayerRewards
-- #            :type string
-- #            :player string
SELECT *
FROM Rewards
WHERE type = :type
  AND player = :player;
-- #}
-- #{       getRewardTotal
-- #            :type string
SELECT reward, SUM(amount) AS total
FROM Rewards
WHERE type = :type
GROUP BY reward;
-- #}
-- #{       resetCrateRewards
-- #            :type string
DELETE
FROM Rewards
WHERE type = :type;
-- #}
-- #{       resetPlayerRewards
-- #            :player string
DELETE
FROM Rewards
WHERE player = :player;
-- #}
-- #{       resetCrateReward
-- #            :type string
-- #            :reward string
DELETE
FROM Rewards
WHERE type = :type
  AND reward = :reward;
-- #}
-- #{       resetPlayerCrateRewards
-- #            :type string
-- #            :player string
DELETE
FROM Rewards
WHERE type = :type
  AND player = :player;
-- #}
-- #{       resetPlayerCrateReward
-- #            :type string
-- #            :player string
-- #            :reward string
DELETE
FROM Rewards
WHERE type = :type
  AND player = :player
  AND reward = :reward;
-- #}
-- #}
-- #}