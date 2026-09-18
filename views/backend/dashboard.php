<?php
include '../../header.php';

?>

<!-- Bootstrap admin dashboard template -->
<div>
    <hr class="my-3">
    <div style="color: black; font-size: 30px; font-family: Montserrat; font-weight: 400; padding-left: 3rem ;word-wrap: break-word">Liens permettant d'administrer le Blog Tinder</div>    
    <hr class="my-3">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <p>Bienvenue sur le dashboard !</p>
            </div>
            <div class="col-md-12">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Objets</th>
                            <th>Actions</th>
                            <th>Commentaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Users</td>
                            <td>
                                <a href="/views/backend/users/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/users/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/users/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/users/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Genres</td>
                            <td>
                                <a href="/views/backend/genres/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/genres/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/genres/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/genres/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Commentaires</td>
                            <td>
                                <a href="/views/backend/comments/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/comments/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/comments/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/comments/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Likes</td>
                            <td>
                                <a href="/views/backend/likes/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/likes/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/likes/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/likes/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Matchs</td>
                            <td>
                                <a href="/views/backend/matchs/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/matchs/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/matchs/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/matchs/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td></td>
                        </tr>
<!--                         <tr>
                            <td>Statuts</td>
                            <td>
                                <a href="/views/backend/statutsCC/list.php" class="btn btn-primary disabled">List</a>
                                <a href="/views/backend/statutsCC/create.php" class="btn btn-success disabled">Create</a>
                                <a href="/views/backend/statutsCC/edit.php" class="btn btn-warning disabled">Edit</a>
                                <a href="/views/backend/statutsCC/delete.php" class="btn btn-danger disabled">Delete</a>
                            </td>
                            <td>
                                <p>CC S2 : Exemple CRUD fourni</p>
                            </td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>