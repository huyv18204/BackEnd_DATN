import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import cors from 'cors';
import Redis from "ioredis";
const redis = new Redis();
const app = express();
const httpServer = createServer(app);
const io = new Server(httpServer, {
    cors: {
        origin: "http://localhost:3000",
        methods: ["GET", "POST"],
        // allowedHeaders: ["my-custom-header"],
        credentials: true
    }
});

app.use(cors()); // Thêm middleware cors cho express

redis.on('connect', () => {
    console.log('Connected to Redis');
});

redis.on('error', (err) => {
    console.error('Redis error:', err);
});





io.on('connection', (socket) => {
    console.log('A user connected');
    // socket.on('disconnect', () => {
    //     // console.log('User disconnected');
    // });
});

redis.psubscribe('*', (err, count) => {
    if (err) {
        console.error('Failed to psubscribe:', err);
    } else {
        console.log(`Pattern subscribed to ${count} channels.`);
    }
});

redis.on('pmessage', (pattern, channel, message) => {
    try {
        message = JSON.parse(message);
        console.log("channel: " + channel , "message: " + message);
        console.log(message.data)
        io.emit(channel, message.data);
    } catch (error) {
        console.error("Error parsing message:", error);
    }
});

httpServer.listen(8080, () => {
    console.log('Listening on port 8080');
});
