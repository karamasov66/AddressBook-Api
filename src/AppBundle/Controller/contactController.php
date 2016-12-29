<?php

namespace AppBundle\Controller;


use AppBundle\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Entity\Contact;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


class ContactController extends Controller
{
    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Récupère la liste des contacts d'un utilisateur",
     *    output= { "class"=Contact::class }
     * )
     *
     * @Rest\View()
     * @Rest\Get("/users/{user_id}/contacts")
     * @return JsonResponse
     * @param Request $request
     */
    public function getContactsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userRep = $em->getRepository('AppBundle:User');
        $user = $userRep->find($request->get('user_id'));

        $contacts = $user->getContacts();
        /** @var $user User */

        return $contacts;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Récupère un contact",
     *    output= { "class"=Contact::class }
     * )
     *
     * @Rest\Get("/contacts/{id}")
     * @Rest\View()
     */
    public function getContactAction(Request $request)
    {

        $contact = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Contact')
            ->find($request->get('id'));

        if (empty($contact)) {
            return \FOS\RestBundle\View\View::create(['message' => 'Contact not found'], Response::HTTP_NOT_FOUND);
        }

        return $contact;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Crée un contact dans l'application",
     *    input={"class"=ContactType::class, "name"=""},
     *    statusCodes = {
     *        201 = "Création avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         201 = {"class"=Contact::class, "groups"={"contact"}},
     *         400 = { "class"=ContactType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     *
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/users/{user_id}/contacts")
     */
    public function postContactAction(Request $request)
    {
        $contact = new Contact();

        $user = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:User')
            ->find($request->get('user_id'));

        $form = $this->createForm(ContactType::class, $contact);
        $form->submit($request->request->all());

        if ($form->isValid()){
            $user->addContact($contact);
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($user);
            $em->flush();
            return $contact;
        }

        return $form;
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Met à jour entièrement un contact",
     *    input={"class"=ContactType::class, "name"=""},
     *    statusCodes = {
     *        200 = "Requête traitée avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         200 = {"class"=Contact::class, "groups"={"contact"}},
     *         400 = { "class"=ContactType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     *
     * @Rest\View()
     * @Rest\Put("/contacts/{id}")
     */
    public function updateContactAction(Request $request)
    {
        return $this->updateContact($request, true);
    }

    /**
     * @ApiDoc(
     *     resource=true,
     *    description="Met à jour partiellement un contact",
     *    input={"class"=ContactType::class, "name"=""},
     *    statusCodes = {
     *        200 = "Requête traitée avec succès",
     *        400 = "Formulaire invalide"
     *    },
     *    responseMap={
     *         200 = {"class"=Contact::class, "groups"={"contact"}},
     *         400 = { "class"=ContactType::class, "form_errors"=true, "name" = ""}
     *    }
     * )
     *
     * @Rest\View()
     * @Rest\Patch("/contacts/{id}")
     */
    public function patchContactAction(Request $request)
    {
        return $this->updateContact($request, false);
    }

    private function updateContact(Request $request, $clearMissing)
    {
        $contact = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Contact')
            ->find($request->get('id'));

        if (empty($contact)) {
            return $this->contactNotFound();
        }

        $form = $this->createForm(ContactType::class, $contact);

        $form->submit($request->request->all(), $clearMissing);

        if ($form->isValid()) {
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($contact);
            $em->flush();
            return $contact;
        } else {
            return $form;
        }
    }

    /**
     * @ApiDoc(
     *    resource=true,
     *    description="Supprime un contact",
     *    statusCodes = {
     *        200 = "Supprimé avec succès",
     *        404 = "Ressource non trouvé"
     *    }
     *
     *)
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/contacts/{id}")
     * @param Request $request
     */
    public function removeContactAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $contact = $em->getRepository("AppBundle:Contact")
            ->find($request->get('id'));
        if ($contact){
            $em->remove($contact);
            $em->flush();
        }
    }

    private function contactNotFound()
    {
        return \FOS\RestBundle\View\View::create(['message' => 'Contact not found'], Response::HTTP_NOT_FOUND);
    }
}