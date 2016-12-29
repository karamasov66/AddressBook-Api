<?php

namespace AppBundle\Controller;


use AppBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


class UserController extends Controller
{
    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Récupère la liste des utilisateurs de l'application",
     *    output= { "class"=User::class }
     * )
     * 
     * @Rest\Get("/users")
     * @Rest\View(serializerGroups={"user"})
     */
    public function getUsersAction()
    {
        $em = $this->getDoctrine()->getManager();
        $rep = $em->getRepository('AppBundle:User');
        $users = $rep->findAll();
        /* @var $users User[] */

        return $users;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Récupère un utilisateur de l'application",
     *    output= { "class"=Contact::class }
     * )
     * 
     * @Rest\Get("/users/{user_id}")
     * @Rest\View(serializerGroups={"user"})
     * @param Request $request
     * @return User 
     */
    public function getUserAction(Request $request)
    {
        $user = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:User')
            ->find($request->get('user_id'));
        /* @var $user User */

        if (empty($user)) {
            return \FOS\RestBundle\View\View::create(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $user;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Crée un utilisateur dans l'application",
     *    input={"class"=UserType::class, "name"=""},
     *    statusCodes = {
     *        201 = "Création avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         201 = {"class"=User::class, "groups"={"user"}},
     *         400 = { "class"=UserType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     * 
     * @Rest\View(serializerGroups={"user"}, statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/users")
     */
    public function postUserAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user, ['validation_groups'=>['Default', 'New']]);
        $form->submit($request->request->all());

        if ($form->isValid()){
            $encoder = $this->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);

            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($user);
            $em->flush();
            return $user;
        }

        return $form;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Met à jour entièrement un utilisateur",
     *    input={"class"=UserType::class, "name"=""},
     *    statusCodes = {
     *        200 = "Requête traitée avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         200 = {"class"=User::class, "groups"={"user"}},
     *         400 = { "class"=UserType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     * 
     * @Rest\View()
     * @Rest\Put("users/{id}")
     */
    public function updateUserAction(Request $request)
    {
        return $this->updateUser($request, true);
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Met à jour partiellement un utilisateur",
     *    input={"class"=UserType::class, "name"=""},
     *    statusCodes = {
     *        200 = "Requête traitée avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         200 = {"class"=User::class, "groups"={"user"}},
     *         400 = { "class"=UserType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     * 
     * @Rest\View()
     * @Rest\Patch("/users/{id}")
     */
    public function patchUserAction(Request $request)
    {
        return $this->updateUser($request, false);
    }

    private function updateUser(Request $request, $clearMissing)
    {
        $user = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:User')
            ->find($request->get('id'));
        /* @var $user User */

        if (empty($user)) {
            return $this->userNotFound();
        }

        $connectedUser = $this->get('security.token_storage')->getToken()->getUser();

        if ($user->getId() !== $connectedUser->getId()){
            return \FOS\RestBundle\View\View::create(['message' => 'Unauthorized request'], Response::HTTP_FORBIDDEN);
        }

        if ($clearMissing) {
            $options = ['validation_groups'=>['Default', 'FullUpdate']];
        } else {
            $options = [];
        }

        $form = $this->createForm(UserType::class, $user, $options);

        $form->submit($request->request->all(), $clearMissing);

        if ($form->isValid()) {
            if (!empty($user->getPlainPassword())) {
                $encoder = $this->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($encoded);
            }
            $em = $this->get('doctrine.orm.entity_manager');
            $em->merge($user);
            $em->flush();
            return $user;
        } else {
            return $form;
        }
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Supprime un utilisateur",
     *     statusCodes = {
     *        200 = "Supprimé avec succès",
     *        404 = "Ressource non trouvé"
     *    }
     *)
     * 
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/users/{id}")
     */
    public function removeUserAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $user = $em->getRepository("AppBundle:User")
            ->find($request->get('id'));

        $connectedUser = $this->get('security.token_storage')->getToken()->getUser();

        if ($user->getId() !== $connectedUser->getId()){
            return \FOS\RestBundle\View\View::create(['message' => 'Unauthorized request'], Response::HTTP_FORBIDDEN);
        }

        if ($user){
            $em->remove($user);
            $em->flush();
        }
    }

    private function userNotFound()
    {
        return \FOS\RestBundle\View\View::create(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
    }
}