<?php

require_once BASE_PATH . '/template/admin/layouts/header.php';

?>

<section class="pt-3 pb-1 mb-2 border-bottom">
    <h1 class="h5">Edit Banner</h1>
</section>
<section class="row my-3">
    <section class="col-12">

        <form method="post" action="<?= url('admin/banner/update/' . $banner['id']) ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="url">Url</label>
                <input type="text" class="form-control" id="url" name="url" placeholder="Enter url ..." value="<?= $banner['url'] ?>">
            </div>

            <div class="form-group">
                <hr />
                <label for="image">
                    Image
                </label>
                <input style="margin-bottom: 0.8rem;" type="file" id="image" name="image" class="form-control-file">
                <img style="width: 100px;" src="<?= asset($banner['image']) ?>" alt="">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">update</button>
        </form>
    </section>
</section>
<?php

require_once BASE_PATH . '/template/admin/layouts/footer.php';

?>