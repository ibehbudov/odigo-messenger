# Odigo Go backend — builds a static binary and serves API + web frontend.
FROM golang:1.25-alpine AS build
WORKDIR /src
COPY backend/go.mod backend/go.sum ./backend/
RUN cd backend && go mod download
COPY backend ./backend
RUN cd backend && CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /odigo-backend .

FROM alpine:latest
RUN apk add --no-cache ca-certificates tzdata
COPY --from=build /odigo-backend /odigo-backend
COPY frontend /frontend
ENV FRONTEND_DIR=/frontend PORT=8080
EXPOSE 8080
ENTRYPOINT ["/odigo-backend"]
