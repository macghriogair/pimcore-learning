<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Example</title>
</head>

<body>

<style type="text/css">
    body {
        padding:0;
        margin: 0;
        font-family: "Lucida Sans Unicode", Arial;
        font-size: 14px;
    }

    #site {
        margin: 0 auto;
        width: 600px;
        padding: 30px 0 0 0;
        color:#65615E;
    }

    h1, h2, h3 {
        font-size: 18px;
        padding: 0 0 5px 0;
        border-bottom: 1px solid #001428;
        margin-bottom: 5px;
    }

    h3 {
        font-size: 14px;
        padding: 15px 0 5px 0;
        margin-bottom: 5px;
        border-color: #cccccc;
    }

    img {
        border: 0;
    }

    p {
        padding: 0 0 5px 0;
    }

    a {
        color: #000;
    }

    #logo {
        text-align: center;
        padding: 50px 0;
    }

    #logo hr {
        display: block;
        height: 1px;
        overflow: hidden;
        background: #BBB;
        border: 0;
        padding:0;
        margin:30px 0 20px 0;
    }

    .claim {
        text-transform: uppercase;
        color:#BBB;
    }

    #site ul {
        padding: 10px 0 10px 20px;
        list-style: circle;
    }

    .buttons {
        margin-bottom: 100px;
        text-align: center;
    }

    .buttons a {
        display: inline-block;
        background: #0078be;
        color:#fff;
        padding: 5px 10px;
        margin-right: 10px;
        width:40%;
        border-radius: 2px;
        text-decoration: none;
    }

    .buttons a:hover {
        background: #1C8BC1;
    }

    .buttons a:last-child {
        margin: 0;
    }

</style>


<div id="site">
    <div id="logo">
        <a href="http://www.pimcore.org/"><img src="/pimcore/static6/img/logo-gray.svg" style="width: 200px;" /></a>
        <hr />
        <div class="claim">
            <?php echo $this->input('myHeadline'); ?>
        </div>

        <?= $this->wysiwyg("specialContent", [
            "height" => 200
        ]); ?>

        <div>
            <?= $this->image("myImage", ["thumbnail" => "square500"] ); ?>
        </div>
    </div>

    <?php if($this->editmode) { ?>
        <?= 
            $this->href('myHref', 
                [   
                    'types' => ['object'], 
                    'subtypes' => ['object' => ['object']], 
                    'classes' => ['Person']
                ]); 
        ?>

    <?php } else { 
        $el = $this->href('myHref')->getElement();
        if ($el instanceof \Pimcore\Model\Object\Person) {
            echo $el->getFirstname() . ' ' . $el->getLastname();
        }
    } ?>
</div>

</body>
</html>
