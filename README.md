# Worklist: High Fidelity's exoskeleton for rapid software development

High Fidelity is an open source virtual world platform. We are building the
software with a mix of full-time developers, part time developers who are paid
here on the worklist, and open source collaborators. As use of the virtual world grows, Worklist will also host paid projects run by other teams.

## Provisioning

### Local (development) environment

Virtual machines can automatically be created from scratch and allow developers
and testers contribute from their own instances.

In order to get started, required software must be present in the host OS:

 * [VirtualBox](https://www.virtualbox.org/)
 * [Vagrant](https://www.vagrantup.com)
 * [Ansible](https://www.ansible.com/)

First-timers will need to install Vagrant's _hostmanager_ plugin:

```shell
$ vagrant plugin install vagrant-hostmanager
```

Now let's spin it up:

```shell
$ vagrant up
```

### Cloud deployment

Officialy, the `stable` branch is being used for deployment into the production
instance. Committing or merging here means that changes passed QA process and
the Code Review one as well.
