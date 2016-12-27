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


class ContactController extends Controller
{
    /**
     * @Rest\Get("/users/{user_id}/contacts")
     * @Rest\View()
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
     * @Rest\View()
     * @Rest\Put("/contacts/{id}")
     * @param Request $request
     */
    public function updateContactAction(Request $request)
    {
        return $this->updateContact($request, true);
    }

    /**
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