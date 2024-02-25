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
-- #{       getByWorld
-- #            :world string
SELECT *
FROM Crates
WHERE world = :world;
-- #}
-- #}

-- #{   rewards
-- #{       set
-- #            :type string
-- #            :player string
-- #            :reward string
-- #            :amount int
INSERT OR
REPLACE INTO Rewards(type, player, reward, amount)
VALUES (:type, :player, :reward, :amount);
-- #}
-- #{       get
-- #            :type string
-- #            :player string
SELECT *
FROM Rewards
WHERE type = :type
  AND player = :player;
-- #}
-- #{       getTotal
-- #            :type string
SELECT reward, SUM(amount) AS total
FROM Rewards
WHERE type = :type
GROUP BY reward;
-- #}
-- #{       reset
-- #            :type string
DELETE
FROM Rewards
WHERE type = :type;
-- #}
-- #{       resetPlayer
-- #            :type string
-- #            :player string
DELETE
FROM Rewards
WHERE type = :type
  AND player = :player;
-- #}
-- #{       resetPlayerReward
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