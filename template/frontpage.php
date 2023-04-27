<div style="padding: 20px;">
    <h1>Welcome to Gem Products Importing Inventory</h1>

    <style>
        .btn {
            background-color: black;
            color: white;
            padding: 8px;
            font-family: inherit;
            border-radius: 10px;
            border: 0px;
        }

        .logs {
            float: right;
            background-color: white;
            border: 1px solid #dfdfdf;
            border-radius: 8px;
            width: 33%;
            height: 33vh;
            margin-right: 20px;
        }

        .logs .title {
            padding: 4px 0px;
            background-color: #dfdfdf;
            width: 100%;
            border-top-right-radius: 7px;
            border-top-left-radius: 7px;
        }

        .log-content {
            padding: 10px;
        }

        #loading {
            color: green;
            font-size: 16px;
            font-weight: 600;
        }
    </style>


    <div class="logs">
        <div class="title">&nbsp;&nbsp;&nbsp;Logs</div>

        <div class="log-content">
            <?= $this->logs ?>
        </div>

    </div>

    <br>
    <?= $err ?>

    <?php

$this->gem_in_array();

?>

    <br>

    <div id="loading"></div>

    <h3>Actions:</h3>

    <form method="post" id="insert_gems" action="">
        <input type="hidden" name="actionprocess" value="insert_into_db">
        <input class="btn" type="submit" id="submitForm" value="Insert Gems into DB">
    </form>

    <div id="response"></div>


    <?php

    echo $ans;

    echo $this->gems_log_text;
    ?>



</div>
