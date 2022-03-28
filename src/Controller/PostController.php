<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\PostRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    #[Route('/', name: 'post')]
    public function index(Request $request, PostRepository $postRepository, PaginatorInterface $paginator): Response
    {
        $posts = $paginator->paginate(
            $postRepository->findAll(),
            $request->query->getInt('page', 1),
            5 
        );

        return $this->render('post/index.html.twig', [
            'posts' => $posts
        ]);
    }

    #[Route('/post/new', name: 'post_new')]
    public function create(Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setCreatedAt(new DateTime());
            $post->setAuthor($this->getUser());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('new_post_success', 'Der Eintrag wurde erfolgreich erstellt!');

            return $this->redirectToRoute('post');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/post/{id}', name: 'post_show')]
    public function show(Request $request, PostRepository $postRepository, ManagerRegistry $doctrine): Response
    {
        $postId = $request->attributes->get('id');
        $post = $postRepository->find($postId);

        /* Kommentarfunktion */
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        $this->addComment($commentForm, $comment, $post, $doctrine);

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm->createView()
        ]);
    }

    #[Route('/post/{id}/edit', name: 'post_edit')]
    public function edit(Post $post, Request $request, ManagerRegistry $doctrine): Response
    {
        if ($this->getUser() != $post->getAuthor() || !$this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('edit_post_success', 'Der Eintrag wurde erfolgreich bearbeitet!');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'editForm' => $form->createView()
        ]);
    }

    // Add comments
    private function addComment($commentForm, $comment, $post, $doctrine)
    {
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setCreatedAt(new DateTimeImmutable());
            $comment->setPost($post);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('new_comment_success', 'Ihr Kommentar wurde erfolgreich erstellt!');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }
    }
}
