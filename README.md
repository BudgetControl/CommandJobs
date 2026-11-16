# BudgetControl Ms template

This repository contains the code of microservice budgetcontrol jobs client application.

## Prerequisites

- Docker: [Install Docker](https://docs.docker.com/get-docker/)
- Task: [Install Task](https://taskfile.dev/#/installation)

## Getting Started

1. Clone this repository:

    ```bash
    git clone https://github.com/BudgetControl/CommandJobs.git
    ```

2. Build and run the Docker containers:

    ```bash
    task build:dev
    ```

## Task Commands

- `task build:dev`: Install and build dev application.
- `task run:db`: Build and run dev database

### Test with mailhog service
You can use an fake mailhog server
- docker run --rm -d --name mailhog -p 8025:8025 -p 1025:1025 mailhog/mailhog
- docker network connect [network_name] mailhog

## Contributing

Contributions are welcome! Please read our [Contribution Guidelines](CONTRIBUTING.md) for more information.

## License

This project is licensed under the [MIT License](LICENSE).
# Scheduled bank synchronization

Set `SYNC_API_BASE_URL` to the internal Sync service URL, `API_SECRET` to Sync's
`SECRET_KEY`, and `SYNC_JOB_SECRET` to the same independent secret configured
in Sync. Configure each bank account with a manual sync first, then schedule:

```sh
php console bank:sync
```

The command returns a nonzero exit status if any account fails. Keep the service
endpoint on the private network.
